<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\BookTag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportEbooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ebooks:import {--limit=10 : 限制本次处理的文件数量}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '从 pending 目录导入电子书文件';

    /**
     * 支持的文件扩展名
     */
    protected array $supportedExts = [
        'azw', 'azw1', 'azw3', 'azw4', 'cb7', 'cbc', 'cbr', 'cbz',
        'chm', 'docx', 'epub', 'fb2', 'fbz', 'html', 'htmlz', 'imp',
        'kepub', 'lit', 'lrf', 'lrx', 'mobi', 'odt', 'oebzip', 'opf',
        'pdb', 'pdf', 'pml', 'pmlz', 'pobi', 'prc', 'rar', 'rb',
        'rtf', 'snb', 'tpz', 'txt', 'txtz', 'updb', 'zip'
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        $limit = max(1, $limit);

        $this->info("开始处理，限制文件数量: {$limit}\n");

        $inputDir =  storage::disk('local')->path('pending/');

        
        $dateSuffix = date('ymd');
        $successDir = storage::disk('local')->path("ebook_files/{$dateSuffix}");

        $duplicateDir = storage::disk('local')->path("duplicate/{$dateSuffix}");
        $coverDir = storage::disk('public')->path("covers/{$dateSuffix}");

        // 确保目录存在
        $this->ensureDirectoryExists($successDir);
        $this->ensureDirectoryExists($duplicateDir);
        $this->ensureDirectoryExists($coverDir);
  

         // 扫描文件
         $files = $this->scanSupportedFiles($inputDir, $limit);

        if (empty($files)) {
            $this->info("没有找到需要处理的文件");
            return 0;
        }

        $this->info("找到 " . count($files) . " 个文件，开始处理...\n");

        $successCount = 0;
        $failCount = 0;

        foreach ($files as $filePath) {
            if ($this->processFile($filePath, $successDir, $duplicateDir, $coverDir)) {
                $successCount++;
            } else {
                $failCount++;
            }
            $this->line('');
        }

        $this->info(str_repeat('=', 50));
        $this->info("处理完成！");
        $this->info("成功: {$successCount} 个");
        $this->info("失败: {$failCount} 个");
        $this->info("总计: " . count($files) . " 个");

        return 0;
    }

    /**
     * 扫描支持的文件
     */
    protected function scanSupportedFiles(string $inputDir, int $limit): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($inputDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (count($files) >= $limit) {
                break;
            }

            if (!$file->isFile() || !$file->isReadable()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (in_array($ext, $this->supportedExts, true)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * 处理单个文件
     */
    protected function processFile(string $filePath, string $successDir, string $duplicateDir, string $coverDir): bool
    {
        $this->info("🚀 开始处理: {$filePath}");

        if (!is_readable($filePath)) {
            $this->error("❌ 文件不可读，跳过");
            return false;
        }

        $fileInfo = new \SplFileInfo($filePath);
        $fileFormat = strtolower($fileInfo->getExtension());
        $fileName = $fileInfo->getFilename();
        $fileSize = $fileInfo->getSize();

        // 计算 MD5
        $md5 = hash_file('md5', $filePath);
        if ($md5 === false) {
            $this->error("❌ 无法计算 MD5，跳过");
            return false;
        }

       

        // 检查是否重复
        if (Book::where('file_md5', $md5)->exists()) {
            $this->warn("⚠️  MD5 已存在，移动到重复目录");
            return $this->handleDuplicateFile($filePath, $fileName, $duplicateDir);
        }

        // 提取元数据
        $meta = $this->extractMetadata($filePath, $md5, $coverDir);
        if ($meta === null) {
            $this->error("❌ 元数据提取失败，跳过");
            return false;
        }

        // 准备目标路径
        $newName = $md5 . '.' . $fileFormat;
        $destPath = $successDir . DIRECTORY_SEPARATOR . $newName;

        // 插入数据库
        try {
            DB::beginTransaction();

            $localPath = storage::disk('local')->path('');
            $publicPath = storage::disk('public')->path('');

            $book = Book::create([
                'file_md5' => $md5,
                'file_name' => $fileName,
                'file_format' => $fileFormat,
                'file_size' => (string) $fileSize,
                'title' => mb_substr($meta['title'] ?? $fileName, 0, 255, 'UTF-8'),
                'author' => mb_substr($meta['authors'] ?? '', 0, 255, 'UTF-8'),
                'description' => $meta['description'] ?? '',
                'languages' => mb_substr($meta['languages'] ?? '', 0, 50, 'UTF-8'),
                'publisher' => mb_substr($meta['publisher'] ?? '', 0, 100, 'UTF-8'),
                'published_at' => $meta['published_at'] ?? null,
                'identifiers' => mb_substr($meta['identifiers'] ?? '', 0, 65535, 'UTF-8'),
                'cover_url' => mb_substr(str_replace($publicPath, '', $meta['cover_path'] ?? ''), 0, 500, 'UTF-8'),
                'file_path' => mb_substr(str_replace($localPath, '', $destPath), 0, 255, 'UTF-8'),
            ]);

            // 处理标签（如果有）
            if (!empty($meta['tags'])) {
                foreach ($meta['tags'] as $tag) {
                    BookTag::create([
                        'book_id' => $book->id,
                        'tag_name' => $tag,
                    ]);
                }
            }

            DB::commit();

            // 移动文件
            if ($this->moveFile($filePath, $destPath)) {
                $this->info("✅ 成功处理并移动到: {$destPath}");
                return true;
            } else {
                $this->error("❌ 移动文件失败");
                DB::rollBack();
                return false;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ 插入数据库失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 提取元数据
     */
    protected function extractMetadata(string $filePath, string $md5, string $coverDir): ?array
    {
        $meta = [
            'title' => null,
            'authors' => null,
            'publisher' => null,
            'languages' => null,
            'published_at' => null,
            'identifiers' => null,
            'description' => null,
            'cover_path' => null,
        ];

        $pathinfo = pathinfo($filePath);
        if (isset($pathinfo['extension']) && strtolower($pathinfo['extension']) === 'txt') {
            $meta['title'] = $pathinfo['filename'];
            // 读取文件内容的前部分作为描述
            if (is_readable($filePath)) {
                $content = $this->readFirst100KBAsUTF8($filePath);
                $content = trim(preg_replace('/\s+/', ' ', $content));
                if ($content !== false) {
                    $meta['description'] = mb_strlen($content, 'UTF-8') > 300
                        ? mb_substr($content, 0, 300, 'UTF-8') . "..."
                        : $content;
                }
            }
            return $meta;
        }

        // 使用 ebook-meta 提取元数据
        $coverPath = $coverDir . DIRECTORY_SEPARATOR . $md5 . '.jpg';
        $cmd = 'CALIBRE_SKIP_QT_INIT=1 ebook-meta --get-cover=' . escapeshellarg($coverPath) . ' ' . escapeshellarg($filePath) . ' 2>/dev/null';
        $output = shell_exec($cmd);

        if (empty($output) || strpos($output, 'Traceback') !== false) {
            $this->warn("ebook-meta 失败: " . substr($output, 0, 500));
            return null;
        }

        // 解析元数据
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (trim($line) === '' || strpos($line, ':') === false) {
                continue;
            }

            [$key, $value] = array_pad(explode(':', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value);

            if ($value === '' || $value === 'Unknown') {
                continue;
            }

            if (preg_match('/^Title$/i', $key)) {
                $meta['title'] = $value;
            } elseif (preg_match('/^Author\(s\)$/i', $key)) {
                $authors = preg_split('/\s*[&,]\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);
                $meta['authors'] = implode(', ', $authors);
            } elseif (preg_match('/^Publisher$/i', $key)) {
                $meta['publisher'] = $value;
            } elseif (preg_match('/^Languages$/i', $key)) {
                $meta['languages'] = $value;
            } elseif (preg_match('/^Published$/i', $key)) {
                $dt = \DateTime::createFromFormat('Y-m-d', $value);
                if ($dt) {
                    $meta['published_at'] = $dt->format('Y-m-d');
                }
            } elseif (preg_match('/^Identifiers$/i', $key)) {
                $meta['identifiers'] = $value;
            } elseif (preg_match('/^Comments$/i', $key)) {
                $meta['description'] = strip_tags($value);
            }
        }

        // 检查封面是否成功提取
        if (is_file($coverPath) && filesize($coverPath) > 1024) {
            $meta['cover_path'] = $coverPath;
        } else {
            if (is_file($coverPath)) {
                unlink($coverPath);
            }
        }

        return $meta;
    }

    /**
     * 读取文件前 100KB 并转换为 UTF-8
     */
    protected function readFirst100KBAsUTF8(string $filename): string
    {
        $handle = fopen($filename, 'rb');
        if ($handle === false) {
            throw new \Exception("无法打开文件: {$filename}");
        }

        $raw = fread($handle, 100 * 1024);
        fclose($handle);

        // 移除 UTF-8 BOM
        if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
            $raw = substr($raw, 3);
        }

        // 检测编码
        $encoding = mb_detect_encoding($raw, ['UTF-8', 'GBK', 'BIG5', 'ASCII'], true);

        // 转换为 UTF-8
        if ($encoding && $encoding !== 'UTF-8') {
            return mb_convert_encoding($raw, 'UTF-8', $encoding);
        }

        return $raw;
    }

    /**
     * 处理重复文件
     */
    protected function handleDuplicateFile(string $filePath, string $fileName, string $duplicateDir): bool
    {
        $dest = $duplicateDir . DIRECTORY_SEPARATOR . $fileName;
        if ($this->moveFile($filePath, $dest)) {
            $this->info("✅ 已移动到重复目录: {$dest}");
            return true;
        } else {
            $this->error("❌ 移动到重复目录失败");
            return false;
        }
    }

    /**
     * 移动文件
     */
    protected function moveFile(string $src, string $dst): bool
    {
        $dstDir = dirname($dst);
        if (!is_dir($dstDir)) {
            if (!mkdir($dstDir, 0755, true)) {
                return false;
            }
        }

        if (@rename($src, $dst)) {
            return true;
        }

        return copy($src, $dst) && unlink($src);
    }

    /**
     * 确保目录存在
     */
    protected function ensureDirectoryExists(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

<?php
/**
 * 电子书导入脚本
 * 
 * 功能：
 * - 从 pending 目录扫描支持的电子书文件
 * - 提取元数据和封面图片
 * - 检查 MD5 重复，避免重复导入
 * - 将数据插入数据库并移动文件到相应目录
 * 
 * 使用方法：
 *   php import_ebooks.php [--limit=N] [--help]
 * 
 * 参数说明：
 *   --limit, -l   限制本次处理的文件数量（默认：10）
 *   --help, -h    显示帮助信息
 * 
 * 示例：
 *   php import_ebooks.php --limit=5
 *   php import_ebooks.php -l 20
 */

declare(strict_types=1);

// 检查是否通过 CLI 执行
if (php_sapi_name() !== 'cli') {
    die("错误: 此脚本只能通过命令行执行\n");
}

// 加载自动加载器
require_once __DIR__ . '/app/Bootstrap/autoload.php';

// 初始化应用
use App\Bootstrap\Bootstrap;
use App\Helper\TimeHelper;
use App\Helper\StringHelper;

Bootstrap::init();
Bootstrap::loadClasses();

// ==================== 配置常量 ====================
const DEFAULT_FILE_LIMIT = 10;
const DEFAULT_SUCCESS_DIR_PREFIX = 'ebook_files';
const DEFAULT_DUPLICATE_DIR_PREFIX = 'duplicate';
const DEFAULT_COVER_DIR_PREFIX = 'covers';
const DEFAULT_INPUT_DIR = 'pending';

// ==================== CLI 参数解析 ====================

/**
 * 解析命令行参数
 * 
 * @return array{limit: int, help: bool}
 */
function parseCliArguments(): array
{
    $options = getopt('l:h', ['limit:', 'help'], $rest_index);
    
    $result = [
        'limit' => DEFAULT_FILE_LIMIT,
        'help' => false,
    ];
    
    if (isset($options['h']) || isset($options['help'])) {
        $result['help'] = true;
        return $result;
    }
    
    // 解析 limit 参数
    $limit = $options['l'] ?? $options['limit'] ?? null;
    if ($limit !== null) {
        $limit = (int) $limit;
        if ($limit > 0) {
            $result['limit'] = $limit;
        } else {
            echo "警告: 无效的 limit 值，使用默认值 " . DEFAULT_FILE_LIMIT . "\n";
        }
    }
    
    return $result;
}

/**
 * 显示帮助信息
 */
function showHelp(): void
{
    echo <<<HELP
电子书导入脚本

用法：
  php import_ebooks.php [选项]

选项：
  -l, --limit=N    限制本次处理的文件数量（默认：{DEFAULT_FILE_LIMIT}）
  -h, --help       显示此帮助信息

示例：
  php import_ebooks.php
  php import_ebooks.php --limit=5
  php import_ebooks.php -l 20

说明：
  - 脚本会从 pending 目录扫描支持的电子书文件
  - 处理后的文件会移动到 ebook_files/YYYYMMDD 目录
  - 重复的文件（相同 MD5）会移动到 duplicate/YYYYMMDD 目录
  - 封面图片会保存到 covers/YYYYMMDD 目录

HELP;
}

// ==================== 目录配置 ====================

/**
 * 获取基础目录配置
 * 
 * @return array{input: string, success: string, duplicate: string, cover: string}
 */
function getDirectoryConfig(): array
{
    $base_dir = __DIR__;
    $date_suffix = date('ymd');
    
    return [
        'input' => $base_dir . DIRECTORY_SEPARATOR . DEFAULT_INPUT_DIR,
        'success' => $base_dir . DIRECTORY_SEPARATOR . DEFAULT_SUCCESS_DIR_PREFIX . DIRECTORY_SEPARATOR . $date_suffix,
        'duplicate' => $base_dir . DIRECTORY_SEPARATOR . DEFAULT_DUPLICATE_DIR_PREFIX . DIRECTORY_SEPARATOR . $date_suffix,
        'cover' => $base_dir . DIRECTORY_SEPARATOR . DEFAULT_COVER_DIR_PREFIX . DIRECTORY_SEPARATOR . $date_suffix,
    ];
}

/**
 * 确保必要的目录存在
 * 
 * @param array<string> $directories 目录路径数组
 * @return bool 是否全部创建成功
 */
function ensureDirectoriesExist(array $directories): bool
{
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                echo "错误: 无法创建目录: $dir\n";
                return false;
            }
        }
    }
    return true;
}

// ==================== 文件扫描 ====================

/**
 * 扫描指定目录中的支持文件
 * 
 * @param string $input_dir 输入目录
 * @param int $limit 最大文件数量
 * @return array<string> 文件路径数组
 */
function scanSupportedFiles(string $input_dir, int $limit): array
{
    if (!is_dir($input_dir)) {
        echo "警告: 输入目录不存在: $input_dir\n";
        return [];
    }
    
    $files = [];
    $supported_exts = \App\Config\Config::getSupportedExts();
    
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($input_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if (count($files) >= $limit) {
                break;
            }
            
            if (!$file->isFile() || !$file->isReadable()) {
                continue;
            }
            
            $ext = strtolower($file->getExtension());
            if (in_array($ext, $supported_exts, true)) {
                $files[] = $file->getPathname();
            }
        }
    } catch (Exception $e) {
        echo "错误: 扫描目录时发生异常: " . $e->getMessage() . "\n";
    }
    
    return $files;
}

// ==================== 文件处理 ====================

/**
 * 检查文件是否已存在（通过 MD5）
 * 
 * @param PDO $pdo 数据库连接
 * @param string $md5 文件 MD5 值
 * @return bool 是否存在
 */
function isFileDuplicate(PDO $pdo, string $md5): bool
{
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM books WHERE file_md5 = ? LIMIT 1");
        $stmt->execute([$md5]);
        return (bool) $stmt->fetch();
    } catch (PDOException $e) {
        echo "错误: 检查重复文件时数据库查询失败: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * 计算文件 MD5 值
 * 
 * @param string $file_path 文件路径
 * @return string|null MD5 值，失败返回 null
 */
function calculateFileMd5(string $file_path): ?string
{
    if (!is_readable($file_path)) {
        return null;
    }
    
    $md5 = hash_file('md5', $file_path);
    return $md5 === false ? null : $md5;
}

/**
 * 移动文件到目标目录
 * 
 * @param string $src 源文件路径
 * @param string $dst 目标文件路径
 * @return bool 是否成功
 */
function moveFile(string $src, string $dst): bool
{
    // 确保目标目录存在
    $dst_dir = dirname($dst);
    if (!is_dir($dst_dir)) {
        if (!mkdir($dst_dir, 0755, true)) {
            return false;
        }
    }
    
    // 尝试直接移动（跨分区会失败）
    if (@rename($src, $dst)) {
        return true;
    }
    
    // 回退到复制+删除
    return copyAndDelete($src, $dst);
}

/**
 * 复制文件并删除源文件
 * 
 * @param string $src 源文件路径
 * @param string $dst 目标文件路径
 * @return bool 是否成功
 */
function copyAndDelete(string $src, string $dst): bool
{
    if (!copy($src, $dst)) {
        return false;
    }
    
    return unlink($src);
}

/**
 * 处理重复文件
 * 
 * @param string $file_path 文件路径
 * @param string $file_name 文件名
 * @param string $duplicate_dir 重复文件目录
 * @return bool 是否成功
 */
function handleDuplicateFile(string $file_path, string $file_name, string $duplicate_dir): bool
{
    $dest = $duplicate_dir . DIRECTORY_SEPARATOR . $file_name;
    
    if (moveFile($file_path, $dest)) {
        echo "✅ 已移动到重复目录: $dest\n";
        return true;
    } else {
        echo "❌ 移动到重复目录失败\n";
        return false;
    }
}

/**
 * 插入书籍数据到数据库
 * 
 * @param PDO $pdo 数据库连接
 * @param array<string, mixed> $file_info 文件信息
 * @param array<string, mixed> $meta 元数据
 * @param string $dest_path 目标文件路径
 * @return bool 是否成功
 */
function insertBookToDatabase(
    PDO $pdo,
    array $file_info,
    array $meta,
    string $dest_path
): bool {
    $sql = "
        INSERT INTO books (
            file_md5, file_name, file_format, file_size, 
            title, author, description, 
            languages, publisher, published_at, identifiers, 
            cover_url, file_path, created_at
        ) VALUES (
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?
        )
    ";
    
    try {
        $stmt = $pdo->prepare($sql);
        
        // 处理数据，确保不超过数据库字段长度限制
        $title = mb_substr($meta['title'] ?? $file_info['name'], 0, 255, 'UTF-8');
        $authors = mb_substr($meta['authors'] ?? '', 0, 255, 'UTF-8');
        $description = $meta['description'] ?? '';
        $languages = mb_substr($meta['languages'] ?? '', 0, 50, 'UTF-8');
        $publisher = mb_substr($meta['publisher'] ?? '', 0, 100, 'UTF-8');
        $identifiers = mb_substr($meta['identifiers'] ?? '', 0, 65535, 'UTF-8');
        $cover_url = mb_substr(str_replace(__DIR__, '', $meta['cover_path'] ?? ''), 0, 500, 'UTF-8');
        $file_path_value = mb_substr(str_replace(__DIR__, '', $dest_path), 0, 255, 'UTF-8');
        $file_name_value = mb_substr($file_info['name'], 0, 255, 'UTF-8');
        
        $stmt->execute([
            $file_info['md5'],
            $file_name_value,
            $file_info['format'],
            $file_info['size'],
            $title,
            $authors,
            $description,
            $languages,
            $publisher,
            $meta['published_at'] ?? null,
            $identifiers,
            $cover_url,
            $file_path_value,
            TimeHelper::getCurrentTime(),
        ]);
        
        return true;
    } catch (PDOException $e) {
        echo "❌ 插入数据库失败: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * 处理单个文件
 * 
 * @param string $file_path 文件路径
 * @param PDO $pdo 数据库连接
 * @param array<string, string> $dirs 目录配置
 * @return bool 是否成功
 */
function processFile(string $file_path, PDO $pdo, array $dirs): bool
{
    echo "🚀 开始处理: $file_path\n";
    
    if (!is_readable($file_path)) {
        echo "❌ 文件不可读，跳过\n";
        return false;
    }
    
    // 获取文件信息
    $file_info_obj = new SplFileInfo($file_path);
    $file_format = strtolower($file_info_obj->getExtension());
    $file_name = $file_info_obj->getFilename();
    $file_size = $file_info_obj->getSize();
    
    // 计算 MD5
    $md5 = calculateFileMd5($file_path);
    if ($md5 === null) {
        echo "❌ 无法计算 MD5，跳过\n";
        return false;
    }
    
    // 检查是否重复
    if (isFileDuplicate($pdo, $md5)) {
        echo "⚠️  MD5 已存在，移动到重复目录\n";
        return handleDuplicateFile($file_path, $file_name, $dirs['duplicate']);
    }
    
    // 提取元数据
    $meta = extractMetadata($file_path, $md5, $dirs['cover']);
    if ($meta === null) {
        echo "❌ 元数据提取失败，跳过\n";
        return false;
    }
    
    // 准备目标路径
    $new_name = $md5 . '.' . $file_format;
    $dest_path = $dirs['success'] . DIRECTORY_SEPARATOR . $new_name;
    
    // 插入数据库
    $file_info = [
        'md5' => $md5,
        'name' => $file_name,
        'format' => $file_format,
        'size' => $file_size,
    ];
    
    if (!insertBookToDatabase($pdo, $file_info, $meta, $dest_path)) {
        return false;
    }
    
    // 移动文件
    if (moveFile($file_path, $dest_path)) {
        echo "✅ 成功处理并移动到: $dest_path\n";
        return true;
    } else {
        echo "❌ 移动文件失败\n";
        // 注意：此时数据库已插入，但文件移动失败，可能需要手动处理
        return false;
    }
}

// ==================== 元数据提取（从原文件保留） ====================

/**
 * 提取电子书完整元数据（支持 Title, Author(s), Publisher, Languages, Published, Identifiers）
 * 
 * @param string $file_path 原始电子书文件路径
 * @param string $md5 文件 MD5（用于封面命名）
 * @param string $cover_dir 封面保存目录（需可写）
 * @return array<string, mixed>|null 成功返回元数据数组，失败返回 null
 */
function extractMetadata(string $file_path, string $md5, string $cover_dir): ?array
{
    // 初始化所有字段
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
    
    // 特殊处理 txt 文件
    $pathinfo = pathinfo($file_path);
    if (isset($pathinfo['extension']) && strtolower($pathinfo['extension']) === 'txt') {
        $meta['title'] = $pathinfo['filename'];
        
        // 读取文件内容的前部分作为描述
        if (is_readable($file_path)) {
            $content = readFirst100KBAsUTF8($file_path);
            $content = trim(preg_replace('/\s+/', ' ', $content));
            
            if ($content !== false) {
                $meta['description'] = mb_strlen($content, 'UTF-8') > 300
                    ? mb_substr($content, 0, 300, 'UTF-8') . "..."
                    : $content;
                $meta['description'] = trim(StringHelper::cleanToUtf8($meta['description']));
            }
        }
        
        return $meta;
    }
    
    // 确保封面目录存在
    if (!is_dir($cover_dir)) {
        if (!mkdir($cover_dir, 0755, true)) {
            echo "警告: 无法创建封面目录: $cover_dir\n";
        }
    }
    
    // 整合元数据提取和封面提取
    $cover_path = $cover_dir . DIRECTORY_SEPARATOR . $md5 . '.jpg';
    $cmd = 'CALIBRE_SKIP_QT_INIT=1 ebook-meta --get-cover=' . escapeshellarg($cover_path) . ' ' . escapeshellarg($file_path) . ' 2>/dev/null';
    $output = shell_exec($cmd);
    
    if (empty($output) || strpos($output, 'Traceback') !== false) {
        error_log("ebook-meta 失败: " . substr($output, 0, 500));
        return null;
    }
    
    // 按行解析元数据
    $lines = explode("\n", $output);
    foreach ($lines as $line) {
        if (trim($line) === '' || strpos($line, ':') === false) {
            continue;
        }
        
        [$key_part, $value_part] = array_pad(explode(':', $line, 2), 2, '');
        $key = trim($key_part);
        $value = trim($value_part);
        
        if ($value === '' || $value === 'Unknown') {
            continue;
        }
        
        // 字段映射
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
            $dt = date_create($value);
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
    if (is_file($cover_path) && filesize($cover_path) > 1024) {
        $meta['cover_path'] = $cover_path;
    } else {
        if (is_file($cover_path)) {
            unlink($cover_path);
        }
    }
    
    return $meta;
}

/**
 * 读取文件前 100KB 并转换为 UTF-8
 * 
 * @param string $filename 文件名
 * @return string 转换后的内容
 */
function readFirst100KBAsUTF8(string $filename): string
{
    if (!file_exists($filename)) {
        throw new Exception("文件不存在: $filename");
    }
    
    $handle = fopen($filename, 'rb');
    if ($handle === false) {
        throw new Exception("无法打开文件: $filename");
    }
    
    $raw = fread($handle, 100 * 1024);
    fclose($handle);
    
    // 移除 UTF-8 BOM（如果存在）
    if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
        $raw = substr($raw, 3);
    }
    
    // 检测编码
    $encoding = mb_detect_encoding($raw, ['UTF-8', 'GBK', 'BIG5', 'ASCII'], true);
    
    // 转换为 UTF-8
    if ($encoding && $encoding !== 'UTF-8') {
        $content = mb_convert_encoding($raw, 'UTF-8', $encoding);
    } else {
        $content = $raw;
    }
    
    return $content;
}

// ==================== 主程序入口 ====================

/**
 * 主函数
 */
function main(): void
{
    // 解析 CLI 参数
    $args = parseCliArguments();
    
    if ($args['help']) {
        showHelp();
        return;
    }
    
    $limit = $args['limit'];
    echo "开始处理，限制文件数量: $limit\n\n";
    
    // 获取数据库连接
    $pdo = \App\Database\Connection::getInstance();
    if (!$pdo) {
        die("错误: 数据库连接失败\n");
    }
    
    // 获取目录配置
    $dirs = getDirectoryConfig();
    
    // 确保目录存在
    if (!ensureDirectoriesExist([$dirs['success'], $dirs['duplicate'], $dirs['cover']])) {
        die("错误: 无法创建必要的目录\n");
    }
    
    // 扫描文件
    $files = scanSupportedFiles($dirs['input'], $limit);
    
    if (empty($files)) {
        echo "没有找到需要处理的文件\n";
        return;
    }
    
    echo "找到 " . count($files) . " 个文件，开始处理...\n\n";
    
    // 处理每个文件
    $success_count = 0;
    $fail_count = 0;
    
    foreach ($files as $file_path) {
        if (processFile($file_path, $pdo, $dirs)) {
            $success_count++;
        } else {
            $fail_count++;
        }
        echo "\n";
    }
    
    // 输出统计信息
    echo str_repeat('=', 50) . "\n";
    echo "处理完成！\n";
    echo "成功: $success_count 个\n";
    echo "失败: $fail_count 个\n";
    echo "总计: " . count($files) . " 个\n";
}

// 执行主程序
main();

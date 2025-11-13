<?php
declare(strict_types=1);

require 'config.php';

// =============== 配置区 ===============
$inputDir      = __DIR__.'/test'; // 修改为你的输入目录
$successDir    = __DIR__.'/ebook';  //处理完成移入的目录
$duplicateDir  = __DIR__.'/duplicate'; // 建议用英文路径
$coverDir      = __DIR__.'/covers';  //封面存储的目录

$supportedExts = [
    'azw', 'azw1', 'azw3', 'azw4', 'cb7', 'cbc', 'cbr', 'cbz', 'chm', 
    'docx', 'epub', 'fb2', 'fbz', 'html', 'htmlz', 'imp', 'kepub', 
    'lit', 'lrf', 'lrx', 'mobi', 'odt', 'oebzip', 'opf', 'pdb', 'pdf', 
    'pml', 'pmlz', 'pobi', 'prc', 'rar', 'rb', 'rtf', 'snb', 'tpz', 
    'txt', 'txtz', 'updb', 'zip'
];
// =====================================

// 创建目录
foreach ([$successDir, $duplicateDir, $coverDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage() . "\n");
}

// 获取所有支持的文件
$files = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($inputDir));
foreach ($iterator as $file) {
    if ($file->isFile()) {
        $ext = strtolower($file->getExtension());
        if (in_array($ext, $supportedExts, true)) {
            $files[] = $file->getPathname();
        }
    }
    if (count($files) >= 10) {
        break;
    }   
}


foreach ($files as $filePath) {
    echo "处理: $filePath\n";
    
    if (!is_readable($filePath)) {
        echo "❌ 文件不可读，跳过\n";
        continue;
    }

    $fileInfo = new SplFileInfo($filePath);
    $ext = strtolower($fileInfo->getExtension());
    $file_name = $fileInfo->getFilename();
    $file_size = $fileInfo->getSize();

    // 计算 MD5
    $md5 = hash_file('md5', $filePath);
    if ($md5 === false) {
        echo "❌ 无法计算 MD5\n";
        continue;
    }
    
    // 检查是否已存在
    $stmt = $pdo->prepare("SELECT 1 FROM books WHERE file_md5 = ?");
    $stmt->execute([$md5]);
    if ($stmt->fetch()) {
        echo "⚠️ MD5 已存在，移动到重复目录\n";
        $dest = $duplicateDir . DIRECTORY_SEPARATOR . $file_name;
        if (@rename($filePath, $dest) || copyAndDelete($filePath, $dest)) {
            echo "✅ 移动到 $dest\n";
        } else {
            echo "❌ 移动失败\n";
        }
        continue;
    }

    // 提取元数据
    $meta = extractMetadata($filePath, $md5, $coverDir);
    if ($meta === null) {
        echo "❌ 元数据提取失败\n";
        continue;
    }

    $newName = $md5 . '.' . $ext;
    $destPath = $successDir . DIRECTORY_SEPARATOR . $newName;
    
    // 插入数据库
    $stmt = $pdo->prepare("
    INSERT INTO books (
        file_md5, file_name, file_format, file_size, 
        title, author, description, 
        languages, publisher, published_at, identifiers, 
        cover_url,file_path,created_at)
    VALUES (
        ?, ?, ?, ?,
        ?, ?, ?, 
        ?, ?, ?, ?,
        ?, ?, NOW())
    ");
    try {
        $stmt->execute([
            $md5, $file_name, $ext, $file_size,
            $meta['title'],     $meta['authors'],       $meta['description'],
            $meta['languages'], $meta['publisher'],     $meta['published_at'], $meta['identifiers'],
            str_replace(__DIR__, '', $meta['cover_path'] ?? "" ) ?? null, str_replace(__DIR__, '', $destPath),
        ]);
    } catch (PDOException $e) {
        echo "❌ 插入数据库失败: " . $e->getMessage() . "\n";
        // continue;
        die();
    }

    // 移动文件
    
    if (@rename($filePath, $destPath) || copyAndDelete($filePath, $destPath)) {
        echo "✅ 成功处理并移动到 $destPath\n";
    } else {
        echo "❌ 移动文件失败\n";
    }
}

echo "所有文件处理完成！\n";

// ==================== 辅助函数 ====================

/**
 * 使用 ebook-meta 提取电子书元数据和封面
 *
 * @param string $filePath 原始电子书文件路径
 * @param string $md5 文件 MD5（用于封面命名）
 * @param string $coverDir 封面保存目录（需可写）
 * @return array|null 成功返回元数据数组，失败返回 null
 */
/**
 * 提取电子书完整元数据（支持 Title, Author(s), Publisher, Languages, Published, Identifiers）
 */
function extractMetadata(string $filePath, string $md5, string $coverDir): ?array {
    if (!is_dir($coverDir)) {
        mkdir($coverDir, 0755, true);
    }

    // 整合元数据提取和封面提取为一步
    $coverPath = $coverDir . DIRECTORY_SEPARATOR . $md5 . '.jpg';
    $cmd = 'CALIBRE_SKIP_QT_INIT=1 ebook-meta --get-cover=' . escapeshellarg($coverPath) . ' ' . escapeshellarg($filePath) . ' 2>/dev/null';
    $output = shell_exec($cmd);

    if (empty($output) || strpos($output, 'Traceback') !== false) {
        error_log("ebook-meta 失败: " . substr($output, 0, 500));
        return null;
    }

    // 初始化所有字段
    $meta = [
        'title'        => null,
        'authors'      => null,
        'publisher'    => null,
        'languages'    => null,
        'published_at' => null,   // 注意：这是 DATETIME，不是字符串
        'identifiers'  => null,
        'description'      => null,
        'cover_path'   => null,
    ];

    // 按行解析
    $lines = explode("\n", $output);
    foreach ($lines as $line) {
        if (trim($line) === '' || strpos($line, ':') === false) continue;

        [$keyPart, $valuePart] = array_pad(explode(':', $line, 2), 2, '');
        $key = trim($keyPart);
        $value = trim($valuePart);

        if ($value === '' || $value === 'Unknown') continue;

        // 字段映射
        if (preg_match('/^Title$/i', $key)) {
            $meta['title'] = $value;
        } elseif (preg_match('/^Author\(s\)$/i', $key)) {
            $authors = preg_split('/\s*[&,]\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);
            $meta['authors'] = implode(', ', $authors);
        } elseif (preg_match('/^Publisher$/i', $key)) {
            $meta['publisher'] = $value;
        } elseif (preg_match('/^Languages$/i', $key)) {
            $meta['languages'] = $value; // 如 "zho" 或 "eng,zho"
        } elseif (preg_match('/^Published$/i', $key)) {
            // 转换 ISO8601 时间为 MySQL DATETIME 格式
            // 输入: 2010-09-01T00:00:00+00:00
            $dt = date_create($value);
            if ($dt) {
                $meta['published_at'] = $dt->format('Y-m-d');
            }
        } elseif (preg_match('/^Identifiers$/i', $key)) {
            $meta['identifiers'] = $value; // 如 "mobi-asin:B00T5Q28CI"
        } elseif (preg_match('/^Comments$/i', $key)) {
            $meta['description'] = strip_tags($value);
        }
    }
    
    // 检查封面是否成功提取
    if (is_file($coverPath) && filesize($coverPath) > 1024) {
        $meta['cover_path'] = $coverPath;
    } else {
        if (is_file($coverPath)) unlink($coverPath);
    }

    return $meta;
}

function copyAndDelete(string $src, string $dst): bool {
    if (copy($src, $dst)) {
        return unlink($src);
    }
    return false;
}
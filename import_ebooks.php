<?php
declare(strict_types=1);

// =============== 配置区 ===============
$inputDir      = __DIR__.'/test/'; // 修改为你的输入目录
$successDir    = __DIR__.'/ebook';
$duplicateDir  = __DIR__.'/duplicate'; // 建议用英文路径
$coverDir      = __DIR__.'/covers';

$dbHost = 'localhost';
$dbName = '7sebook_com';
$dbUser = '7sebook_com';
$dbPass = 'inSZGYaiQhJxPTwQ';

$supportedExts = [
    'epub', 'mobi', 'azw3', 'fb2', 'djvu',
    'doc', 'docx', 'odt', 'odf', 'chm'
];
// =====================================

// 创建目录
foreach ([$successDir, $duplicateDir, $coverDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
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
}

echo "找到 " . count($files) . " 个电子书文件\n";


foreach ($files as $filePath) {
    echo "处理: $filePath\n";
    
    if (!is_readable($filePath)) {
        echo "❌ 文件不可读，跳过\n";
        continue;
    }

    $fileInfo = new SplFileInfo($filePath);
    $ext = strtolower($fileInfo->getExtension());
    $filename = $fileInfo->getFilename();
    $size = $fileInfo->getSize();

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
        $dest = $duplicateDir . DIRECTORY_SEPARATOR . $filename;
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

    // 插入数据库
    $stmt = $pdo->prepare("
        INSERT INTO books (md5, filename, format, file_size, title, authors, translators, summary, cover_path)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    try {
        $stmt->execute([
            $md5,
            $filename,
            $ext,
            $size,
            $meta['title'] ?? null,
            $meta['authors'] ?? null,
            $meta['translators'] ?? null,
            $meta['summary'] ?? null,
            $meta['cover_path'] ?? null
        ]);
    } catch (PDOException $e) {
        echo "❌ 插入数据库失败: " . $e->getMessage() . "\n";
        continue;
    }

    // 移动文件
    $newName = $md5 . '.' . $ext;
    $destPath = $successDir . DIRECTORY_SEPARATOR . $newName;
    if (@rename($filePath, $destPath) || copyAndDelete($filePath, $destPath)) {
        echo "✅ 成功处理并移动到 $destPath\n";
    } else {
        echo "❌ 移动文件失败\n";
    }
}

echo "所有文件处理完成！\n";

// ==================== 辅助函数 ====================

function extractMetadata(string $filePath, string $md5, string $coverDir): ?array {
    // 调用 ebook-meta --json
    $jsonOutput = shell_exec("ebook-meta --json " . escapeshellarg($filePath) . " 2>/dev/null");
    if (empty($jsonOutput)) {
        return null;
    }

    $data = json_decode($jsonOutput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }

    $meta = [
        'title' => $data['title'] ?? null,
        'authors' => !empty($data['authors']) ? implode(', ', (array)$data['authors']) : null,
        'translators' => $data['translators'] ?? null, // Calibre 通常无此字段
        'summary' => $data['comments'] ?? null,
        'cover_path' => null
    ];

    // 提取封面
    $coverPath = $coverDir . DIRECTORY_SEPARATOR . $md5 . '.jpg';
    $cmd = "ebook-meta --get-cover=" . escapeshellarg($coverPath) . " " . escapeshellarg($filePath) . " 2>/dev/null";
    exec($cmd, $output, $returnCode);
    if ($returnCode === 0 && file_exists($coverPath)) {
        $meta['cover_path'] = $coverPath;
    }

    return $meta;
}

function copyAndDelete(string $src, string $dst): bool {
    if (copy($src, $dst)) {
        return unlink($src);
    }
    return false;
}
<?php
// 导入电子书功能脚本，只允许通过cli执行

declare(strict_types=1);

// 检查是否通过cli执行
if (php_sapi_name() !== 'cli') {
    die("请通过cli执行\n");
}

require 'common.php';

// =============== 配置区 ===============
$input_dir      = __DIR__.'/pending'; // 修改为你的输入目录
$success_dir    = __DIR__.'/ebook_files/' . date("ymd");  //处理完成移入的目录
$duplicate_dir  = __DIR__.'/duplicate/'. date("ymd"); // 建议用英文路径
$cover_dir      = __DIR__.'/covers/'. date("ymd");  //封面存储的目录

// 使用 common.php 中定义的支持格式列表
$supported_exts = SUPPORTED_EXTS;
// =====================================

// 创建目录
foreach ([$success_dir, $duplicate_dir, $cover_dir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// 使用 common.php 中的数据库连接
if (!$pdo) {
    die("数据库连接失败\n");
}

// 获取所有支持的文件
$files = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($input_dir));
foreach ($iterator as $file) {
    if ($file->isFile()) {
        $ext = strtolower($file->getExtension());
        if (in_array($ext, $supported_exts, true)) {
            $files[] = $file->getPathname();
        }
    }
    if (count($files) >= 10) {
        break;
    }   
}


foreach ($files as $file_path) {
    echo "🚀 开始处理: $file_path\n";
    
    if (!is_readable($file_path)) {
        echo "❌ 文件不可读，跳过\n";
        continue;
    }

    $file_info = new SplFileInfo($file_path);
    $file_format = strtolower($file_info->getExtension());
    $file_name = $file_info->getFilename();
    $file_size = $file_info->getSize();

    // 计算 MD5
    $md5 = hash_file('md5', $file_path);
    if ($md5 === false) {
        echo "❌ 无法计算 MD5\n";
        continue;
    }
    
    // 检查是否已存在
    $stmt = $pdo->prepare("SELECT 1 FROM books WHERE file_md5 = ?");
    $stmt->execute([$md5]);
    if ($stmt->fetch()) {
        echo "⚠️ MD5 已存在，移动到重复目录\n";
        $dest = $duplicate_dir . DIRECTORY_SEPARATOR . $file_name;

        if (@rename($file_path, $dest) || copy_and_delete($file_path, $dest)) {
            echo "✅ 移动到 $dest\n";
        } else {
            echo "❌ 移动失败\n";
        }
        continue;
    }

    // 提取元数据
    $meta = extract_metadata($file_path, $md5, $cover_dir);
    if ($meta === null) {
        echo "❌ 元数据提取失败\n";
        continue;
    }

    $new_name = $md5 . '.' . $file_format;
    $dest_path = $success_dir . DIRECTORY_SEPARATOR . $new_name;
    

    // 插入数据库
    $current_time = get_current_time();
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
        ?, ?, ?)
    ");
    try {
        $stmt->execute([
            $md5, $file_name, $file_format, $file_size,
            $meta['title'] ?? $file_name,     $meta['authors'],       $meta['description'],
            $meta['languages'], $meta['publisher'],     $meta['published_at'], $meta['identifiers'],
            str_replace(__DIR__, '', $meta['cover_path'] ?? "" ) ?? null, str_replace(__DIR__, '', $dest_path), $current_time,
        ]);
    } catch (PDOException $e) {
        echo "❌ 插入数据库失败: " . $e->getMessage() . "\n";
        // continue;
        die();
    }

    // 移动文件
    
    if (@rename($file_path, $dest_path) || copy_and_delete($file_path, $dest_path)) {
        echo "✅ 成功处理并移动到 $dest_path\n";
    } else {
        echo "❌ 移动文件失败\n";
    }
}

echo "所有文件处理完成！\n";

// ==================== 辅助函数 ====================

/**
 * 使用 ebook-meta 提取电子书元数据和封面
 *
 * @param string $file_path 原始电子书文件路径
 * @param string $md5 文件 MD5（用于封面命名）
 * @param string $cover_dir 封面保存目录（需可写）
 * @return array|null 成功返回元数据数组，失败返回 null
 */
/**
 * 提取电子书完整元数据（支持 Title, Author(s), Publisher, Languages, Published, Identifiers）
 */
function extract_metadata(string $file_path, string $md5, string $cover_dir): ?array {
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

    // 特殊处理txt文件
    $pathinfo = pathinfo($file_path);
    if (isset($pathinfo['extension']) && strtolower($pathinfo['extension']) === 'txt') {
        $meta[ 'title' ] = $pathinfo['filename'];
        // 读取文件内容的前150个字符作为描述
        if (is_readable($file_path)) {
            $content = file_get_contents($file_path);
            if ($content !== false) {
                // 兼容中文的字符截取
                $meta['description'] = mb_strlen($content, 'UTF-8') > 150 ? mb_substr($content, 0, 150, 'UTF-8') : $content;
            }
        }
        
        return $meta;
    }
    
    if (!is_dir($cover_dir)) {
        mkdir($cover_dir, 0755, true);
    }

    // 整合元数据提取和封面提取为一步
    $cover_path = $cover_dir . DIRECTORY_SEPARATOR . $md5 . '.jpg';
    $cmd = 'CALIBRE_SKIP_QT_INIT=1 ebook-meta --get-cover=' . escapeshellarg($cover_path) . ' ' . escapeshellarg($file_path) . ' 2>/dev/null';
    $output = shell_exec($cmd);

    if (empty($output) || strpos($output, 'Traceback') !== false) {
        error_log("ebook-meta 失败: " . substr($output, 0, 500));
        return null;
    }

   

    // 按行解析
    $lines = explode("\n", $output);
    foreach ($lines as $line) {
        if (trim($line) === '' || strpos($line, ':') === false) continue;

        [$key_part, $value_part] = array_pad(explode(':', $line, 2), 2, '');
        $key = trim($key_part);
        $value = trim($value_part);

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
    
    if (is_file($cover_path) && filesize($cover_path) > 1024) {
        $meta['cover_path'] = $cover_path;
    } else {
        if (is_file($cover_path)) unlink($cover_path);
    }

    return $meta;
}

function copy_and_delete(string $src, string $dst): bool {
    if (copy($src, $dst)) {
        return unlink($src);
    }
    return false;
}
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
        // 确保文本字段不超过数据库限制，同时兼容中文字符
        // 根据新表结构进行字段长度适配
        $title = mb_substr($meta['title'] ?? $file_name, 0, 255, 'UTF-8');
        $authors = mb_substr($meta['authors'] ?? '', 0, 255, 'UTF-8');
        $description = $meta['description'] ?? '';
        $languages = mb_substr($meta['languages'] ?? '', 0, 50, 'UTF-8');
        $publisher = mb_substr($meta['publisher'] ?? '', 0, 100, 'UTF-8');
        $identifiers = mb_substr($meta['identifiers'] ?? '', 0, 65535, 'UTF-8'); // TEXT字段
        $cover_url = mb_substr(str_replace(__DIR__, '', $meta['cover_path'] ?? ""), 0, 500, 'UTF-8');
        $file_path_value = mb_substr(str_replace(__DIR__, '', $dest_path), 0, 255, 'UTF-8');
        $file_name_value = mb_substr($file_name, 0, 255, 'UTF-8');
        // $file_size_value = mb_substr((string)$file_size, 0, 10, 'UTF-8');
        
        $stmt->execute([
            $md5, 
            $file_name_value,
            $file_format, 
            $file_size,
            $title,     
            $authors,       
            $description,
            $languages, 
            $publisher,     
            $meta['published_at'], 
            $identifiers,
            $cover_url, 
            $file_path_value, 
            $current_time,
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
        // 读取文件内容的前300个字符作为描述（只读取前10KB，足够提取简介）
        if (is_readable($file_path)) {
            $content = read_txt_file_as_utf8($file_path, 10240); // 只读取前10KB
            // 使用 preg_replace 去除所有空白字符（包括空格、换行、回车、制表符等）
            $content = trim( preg_replace('/[\s\x{3000}]+/u', ' ', $content) );
            if ($content !== false) {
                // 兼容中文的字符截取
                $meta['description'] = mb_strlen($content, 'UTF-8') > 300 ? mb_substr($content, 0, 300, 'UTF-8')."..." : $content;
                // 去除首尾空格
                $meta['description'] = trim(clean_to_utf8($meta['description']));
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

/**
 * 读取TXT文件并转换为UTF-8编码（优化：支持只读取文件开头部分，适用于大文件）
 * 
 * @param string $filename 文件路径
 * @param int|null $max_bytes 最大读取字节数，null表示读取全部（默认null）
 * @return string UTF-8编码的文件内容
 * @throws Exception 文件不存在或无法读取时抛出异常
 */
function read_txt_file_as_utf8($filename, ?int $max_bytes = null) {
    if (!file_exists($filename)) {
        throw new Exception("文件不存在: $filename");
    }

    // 1. 使用文件句柄读取，支持限制读取字节数
    $handle = fopen($filename, 'rb');
    if ($handle === false) {
        throw new Exception("无法打开文件: $filename");
    }

    try {
        // 如果指定了最大字节数，只读取指定长度；否则读取全部
        if ($max_bytes !== null && $max_bytes > 0) {
            $content = fread($handle, $max_bytes);
        } else {
            // 读取全部内容（适用于小文件）
            $content = stream_get_contents($handle);
        }
        
        if ($content === false) {
            throw new Exception("无法读取文件: $filename");
        }
    } finally {
        fclose($handle);
    }

    // 2. 改进的编码检测逻辑（优先检测中文编码，解决乱码问题）
    $detectedEncoding = false;
    
    // 2.1 首先检查是否为有效的UTF-8
    if (mb_check_encoding($content, 'UTF-8')) {
        // 检查是否包含UTF-8替换字符（说明之前编码识别错误导致的乱码）
        if (!preg_match('/[\x{FFFD}]/u', $content)) {
            $detectedEncoding = 'UTF-8';
        }
    }
    
    // 2.2 如果不是UTF-8，优先尝试中文编码（GBK/GB2312最常见）
    if ($detectedEncoding === false) {
        // 中文编码优先级：GBK > GB2312 > GB18030 > BIG5
        $chineseEncodings = ['GBK', 'GB2312', 'GB18030', 'BIG5'];
        $bestEncoding = null;
        $bestScore = 0;
        
        foreach ($chineseEncodings as $encoding) {
            // 尝试转换并验证结果
            $converted = @mb_convert_encoding($content, 'UTF-8', $encoding);
            if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
                // 检查是否包含替换字符（乱码标记）
                if (preg_match('/[\x{FFFD}]/u', $converted)) {
                    continue; // 包含乱码，跳过这个编码
                }
                
                // 计算有效中文字符的得分
                $score = 0;
                // 中文字符范围
                if (preg_match_all('/[\x{4e00}-\x{9fff}]/u', $converted, $matches)) {
                    $score += count($matches[0]);
                }
                // 中文标点符号
                if (preg_match_all('/[\x{3000}-\x{303f}\x{ff00}-\x{ffef}]/u', $converted, $matches)) {
                    $score += count($matches[0]) * 0.5;
                }
                
                // 选择得分最高的编码
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestEncoding = $encoding;
                }
            }
        }
        
        if ($bestEncoding !== null) {
            $detectedEncoding = $bestEncoding;
        }
    }
    
    // 2.3 如果还是没检测到，使用mb_detect_encoding作为备选（调整优先级）
    if ($detectedEncoding === false) {
        $encodings = [
            'GBK', 'GB2312', 'GB18030', 'BIG5',  // 优先中文编码
            'UTF-8',
            'UTF-16LE', 'UTF-16BE',
            'SJIS', 'EUC-JP',
            'EUC-KR',
            'ISO-8859-1', 'ISO-8859-15',
            'Windows-1251', 'Windows-1252',
            'ASCII'
        ];
        
        $detectedEncoding = mb_detect_encoding($content, $encodings, true);
    }
    
    // 2.4 如果仍然无法检测，默认尝试GBK（中文文件最常见）
    if ($detectedEncoding === false) {
        $detectedEncoding = 'GBK';
    }

    // 3. 转换为 UTF-8 并验证结果
    if ($detectedEncoding !== 'UTF-8') {
        $converted = @mb_convert_encoding($content, 'UTF-8', $detectedEncoding);
        if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
            // 检查转换结果是否包含乱码标记
            if (!preg_match('/[\x{FFFD}]/u', $converted)) {
                $content = $converted;
            } else {
                // 如果包含乱码，尝试其他编码（GBK作为最后备选）
                if ($detectedEncoding !== 'GBK') {
                    $fallback = @mb_convert_encoding($content, 'UTF-8', 'GBK');
                    if ($fallback !== false && mb_check_encoding($fallback, 'UTF-8') && 
                        !preg_match('/[\x{FFFD}]/u', $fallback)) {
                        $content = $fallback;
                    } else {
                        $content = $converted; // 使用原始转换结果
                    }
                } else {
                    $content = $converted; // 已经是GBK了，使用转换结果
                }
            }
        } else {
            // 转换失败，尝试GBK作为最后备选
            if ($detectedEncoding !== 'GBK') {
                $fallback = @mb_convert_encoding($content, 'UTF-8', 'GBK');
                if ($fallback !== false && mb_check_encoding($fallback, 'UTF-8')) {
                    $content = $fallback;
                }
            }
        }
    }

    return $content;
}
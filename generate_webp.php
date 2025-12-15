<?php

/**
 * WebP 图片生成脚本
 * 
 * 此脚本由 nginx 在请求优化图片时自动调用
 * 如果优化后的图片不存在，则自动生成
 */

// 加载自动加载器
require_once __DIR__ . '/app/Bootstrap/autoload.php';

use App\Service\ImageService;

// 获取请求的图片路径
// 优先从查询参数获取（nginx rewrite 传递）
$requestPath = $_GET['path'] ?? '';

// 如果没有查询参数，从 REQUEST_URI 获取
if (empty($requestPath)) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    // 移除查询字符串
    $requestUri = strtok($requestUri, '?');
    // 提取路径，如 /covers_sm/251119/46af8b1fa16c97986007e8dcdfd63fd8.webp
    $requestPath = $requestUri;
}

// 确保路径以 /covers_sm/ 开头
if (strpos($requestPath, '/covers_sm/') !== 0) {
    $requestPath = '/covers_sm/' . ltrim($requestPath, '/');
}

// 转换为原图路径
// /covers_sm/251119/46af8b1fa16c97986007e8dcdfd63fd8.webp
// -> /covers/251119/46af8b1fa16c97986007e8dcdfd63fd8.jpg
$originalPath = str_replace('/covers_sm/', '/covers/', $requestPath);
$originalPath = preg_replace('/\.webp$/i', '.jpg', $originalPath);

// 如果原图路径不存在，尝试其他格式
$rootDir = __DIR__;
$fullOriginalPath = $rootDir . $originalPath;

if (!file_exists($fullOriginalPath)) {
    // 尝试其他扩展名
    $extensions = ['png', 'jpeg', 'gif', 'webp'];
    $found = false;
    foreach ($extensions as $ext) {
        $testPath = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '.' . $ext, $originalPath);
        $fullTestPath = $rootDir . $testPath;
        if (file_exists($fullTestPath)) {
            $originalPath = $testPath;
            $fullOriginalPath = $fullTestPath;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        http_response_code(404);
        header('Content-Type: text/plain');
        echo 'Original image not found';
        exit;
    }
}

// 生成优化后的图片
$optimizedPath = ImageService::getOptimizedPath($originalPath);
$fullOptimizedPath = $rootDir . $optimizedPath;

// 尝试生成图片
if (ImageService::convertToWebP($originalPath, $optimizedPath)) {
    // 如果生成成功，输出图片
    if (file_exists($fullOptimizedPath)) {
        header('Content-Type: image/webp');
        header('Cache-Control: public, max-age=31536000'); // 缓存1年
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        readfile($fullOptimizedPath);
        exit;
    }
}

// 如果生成失败，返回404或原图
http_response_code(404);
header('Content-Type: text/plain');
echo 'Failed to generate optimized image';

<?php

declare(strict_types=1);

namespace App\Service;

/**
 * 图片处理服务类
 * 
 * 处理图片格式转换、缩放等操作
 */
class ImageService
{
    /**
     * 将图片转换为 WebP 格式并缩放
     * 
     * @param string $sourcePath 源图片路径（相对于网站根目录）
     * @param string $targetPath 目标图片路径（相对于网站根目录）
     * @param int $maxWidth 最大宽度（像素）
     * @param int $quality WebP 质量（0-100）
     * @return bool 是否成功
     */
    public static function convertToWebP(string $sourcePath, string $targetPath, int $maxWidth = 200, int $quality = 85): bool
    {
        // 检查 GD 扩展是否可用
        if (!extension_loaded('gd')) {
            error_log('GD extension is not loaded');
            return false;
        }

        // 检查 WebP 支持
        if (!function_exists('imagewebp')) {
            error_log('WebP support is not available');
            return false;
        }

        // 获取网站根目录
        $rootDir = dirname(__DIR__, 2);
        $fullSourcePath = $rootDir . '/' . ltrim($sourcePath, '/');
        $fullTargetPath = $rootDir . '/' . ltrim($targetPath, '/');

        // 检查源文件是否存在
        if (!file_exists($fullSourcePath) || !is_readable($fullSourcePath)) {
            error_log("Source image not found: {$fullSourcePath}");
            return false;
        }

        // 创建目标目录
        $targetDir = dirname($fullTargetPath);
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                error_log("Failed to create directory: {$targetDir}");
                return false;
            }
        }

        // 获取图片信息
        $imageInfo = @getimagesize($fullSourcePath);
        if ($imageInfo === false) {
            error_log("Invalid image file: {$fullSourcePath}");
            return false;
        }

        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // 计算新尺寸（保持宽高比）
        $ratio = $sourceWidth / $sourceHeight;
        if ($sourceWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int)($maxWidth / $ratio);
        } else {
            $newWidth = $sourceWidth;
            $newHeight = $sourceHeight;
        }

        // 根据 MIME 类型加载图片
        $sourceImage = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = @imagecreatefromjpeg($fullSourcePath);
                break;
            case 'image/png':
                $sourceImage = @imagecreatefrompng($fullSourcePath);
                break;
            case 'image/gif':
                $sourceImage = @imagecreatefromgif($fullSourcePath);
                break;
            case 'image/webp':
                $sourceImage = @imagecreatefromwebp($fullSourcePath);
                break;
            default:
                error_log("Unsupported image type: {$mimeType}");
                return false;
        }

        if ($sourceImage === false) {
            error_log("Failed to load image: {$fullSourcePath}");
            return false;
        }

        // 创建新图片
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // 处理 PNG 和 GIF 的透明度
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefill($newImage, 0, 0, $transparent);
        }

        // 缩放图片
        imagecopyresampled(
            $newImage,
            $sourceImage,
            0, 0, 0, 0,
            $newWidth,
            $newHeight,
            $sourceWidth,
            $sourceHeight
        );

        // 保存为 WebP
        $result = imagewebp($newImage, $fullTargetPath, $quality);

        // 释放内存
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        if ($result === false) {
            error_log("Failed to save WebP image: {$fullTargetPath}");
            return false;
        }

        return true;
    }

    /**
     * 从原图路径生成优化后的图片路径
     * 
     * @param string $originalPath 原图路径，如 /covers/251119/46af8b1fa16c97986007e8dcdfd63fd8.jpg
     * @return string 优化后的路径，如 /covers_sm/251119/46af8b1fa16c97986007e8dcdfd63fd8.webp
     */
    public static function getOptimizedPath(string $originalPath): string
    {
        if (empty($originalPath)) {
            return '';
        }

        // 移除开头的斜杠
        $path = ltrim($originalPath, '/');

        // 替换目录名
        $path = str_replace('covers/', 'covers_sm/', $path);

        // 替换文件扩展名为 .webp
        $path = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '.webp', $path);

        return '/' . $path;
    }

    /**
     * 检查优化后的图片是否存在，如果不存在则生成
     * 
     * @param string $originalPath 原图路径
     * @return string 优化后的图片路径
     */
    public static function ensureOptimizedImage(string $originalPath): string
    {
        if (empty($originalPath)) {
            return '';
        }

        $optimizedPath = self::getOptimizedPath($originalPath);
        $rootDir = dirname(__DIR__, 2);
        $fullOptimizedPath = $rootDir . '/' . ltrim($optimizedPath, '/');

        // 如果优化后的图片不存在，尝试生成
        if (!file_exists($fullOptimizedPath)) {
            self::convertToWebP($originalPath, $optimizedPath);
        }

        return $optimizedPath;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

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
     * @param string $sourcePath 源图片路径（相对于 public 目录，如 covers/251117/xxx.jpg）
     * @param string $targetPath 目标图片路径（相对于 public 目录，如 covers_sm/251117/xxx.webp）
     * @param int $maxWidth 最大宽度（像素）
     * @param int $quality WebP 质量（0-100）
     * @return bool 是否成功
     */
    public static function convertToWebP(string $sourcePath, string $targetPath, int $maxWidth = 200, int $quality = 85): bool
    {
        // 检查 GD 扩展是否可用
        if (!extension_loaded('gd')) {
            \Log::error('GD extension is not loaded');
            return false;
        }

        // 检查 WebP 支持
        if (!function_exists('imagewebp')) {
            \Log::error('WebP support is not available');
            return false;
        }

        // 获取 public 目录路径
        $publicPath = public_path();
        // 移除开头的斜杠，确保路径正确
        $sourcePath = ltrim($sourcePath, '/');
        $targetPath = ltrim($targetPath, '/');
        $fullSourcePath = $publicPath . '/' . $sourcePath;
        $fullTargetPath = $publicPath . '/' . $targetPath;

        // 检查源文件是否存在
        if (!file_exists($fullSourcePath) || !is_readable($fullSourcePath)) {
            \Log::error("Source image not found: {$fullSourcePath}");
            return false;
        }

        // 创建目标目录
        $targetDir = dirname($fullTargetPath);
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                \Log::error("Failed to create directory: {$targetDir}");
                return false;
            }
        }

        // 获取图片信息
        $imageInfo = @getimagesize($fullSourcePath);
        if ($imageInfo === false) {
            \Log::error("Invalid image file: {$fullSourcePath}");
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
                \Log::error("Unsupported image type: {$mimeType}");
                return false;
        }

        if ($sourceImage === false) {
            \Log::error("Failed to load image: {$fullSourcePath}");
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
            \Log::error("Failed to save WebP image: {$fullTargetPath}");
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
            return asset('images/no-cover.svg');
        }

        // 移除开头的斜杠
        $path = ltrim($originalPath, '/');
        // 替换目录名
        $path = str_replace('covers/', 'covers_sm/', $path);

  
        return Storage::url($path.'.webp');
    }
}

<?php

namespace App\Helpers;

use App\Services\ImageService;

/**
 * 图片辅助函数
 */
class ImageHelper
{
    /**
     * 获取缩略图 URL
     * 
     * @param string|null $coverUrl 原图 URL，如 /covers/251117/a77a9d23b5cdb58b253c6ac51ae42401.jpg
     * @return string 缩略图 URL，如 /covers_sm/251117/a77a9d23b5cdb58b253c6ac51ae42401.webp
     */
    public static function getThumbnailUrl(?string $coverUrl): string
    {
        if (empty($coverUrl)) {
            return '';
        }

        // 如果已经是缩略图 URL，直接返回
        if (strpos($coverUrl, '/covers_sm/') !== false) {
            return $coverUrl;
        }

        // 生成缩略图路径
        return ImageService::getOptimizedPath($coverUrl);
    }
}

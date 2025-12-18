<?php

namespace App\Http\Controllers;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ImageController extends Controller
{
    /**
     * 处理缩略图请求
     * 
     * @param Request $request
     * @param string $path 图片路径，如 251117/a77a9d23b5cdb58b253c6ac51ae42401.webp
     * @return Response
     */
    public function thumbnail(Request $request, string $path)
    {
        // 清理路径，移除多余的斜杠
        $path = trim($path, '/');
        
        // 确保路径以 .webp 结尾
        if (!preg_match('/\.webp$/i', $path)) {
            $path = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '.webp', $path);
        }

        // 构建优化后的图片路径（相对于 public 目录）
        $optimizedPath = 'covers_sm/' . $path;
        $fullOptimizedPath = public_path($optimizedPath);

        // 如果缩略图已存在，直接返回
        if (file_exists($fullOptimizedPath) && is_readable($fullOptimizedPath)) {
            return response()->file($fullOptimizedPath, [
                'Content-Type' => 'image/webp',
                'Cache-Control' => 'public, max-age=31536000', // 缓存1年
                'Expires' => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
            ]);
        }

        // 构建原图路径
        // 从 covers_sm/251117/xxx.webp 转换为 covers/251117/xxx.jpg
        $originalPath = str_replace('covers_sm/', 'covers/', $optimizedPath);
        $originalPath = preg_replace('/\.webp$/i', '.jpg', $originalPath);
        $fullOriginalPath = public_path($originalPath);

        // 如果原图不存在，尝试其他扩展名
        if (!file_exists($fullOriginalPath)) {
            $extensions = ['png', 'jpeg', 'gif', 'webp'];
            $found = false;
            
            foreach ($extensions as $ext) {
                $testPath = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '.' . $ext, $originalPath);
                $fullTestPath = public_path($testPath);
                
                if (file_exists($fullTestPath)) {
                    $originalPath = $testPath;
                    $fullOriginalPath = $fullTestPath;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                Log::warning("Original image not found for: {$optimizedPath}");
                abort(404, 'Original image not found');
            }
        }

        // 生成缩略图（路径相对于 public 目录）
        if (ImageService::convertToWebP($originalPath, $optimizedPath)) {
            // 如果生成成功，返回图片
            if (file_exists($fullOptimizedPath)) {
                return response()->file($fullOptimizedPath, [
                    'Content-Type' => 'image/webp',
                    'Cache-Control' => 'public, max-age=31536000', // 缓存1年
                    'Expires' => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
                ]);
            }
        }

        // 如果生成失败，返回404
        Log::error("Failed to generate optimized image: {$optimizedPath}");
        abort(404, 'Failed to generate optimized image');
    }
}

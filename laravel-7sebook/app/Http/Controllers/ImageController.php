<?php

namespace App\Http\Controllers;

use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            $path .= '.webp';
        }
   


    
        // 构建优化后的图片路径（相对于 public 目录）
        $optimizedPath = 'covers_sm/' . $path;
        $fullOptimizedPath =  Storage::disk('public')->path($optimizedPath);

   
        // 如果缩略图已存在，直接返回
        // if (file_exists($fullOptimizedPath) && is_readable($fullOptimizedPath)) {
        //     return response()->file($fullOptimizedPath, [
        //         'Content-Type' => 'image/webp',
        //         'Cache-Control' => 'public, max-age=31536000', // 缓存1年
        //         'Expires' => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
        //     ]);
        // }




        // 构建原图路径
        // 从 covers_sm/251117/xxx.webp 转换为 covers/251117/xxx.jpg
        $originalPath = 'covers/' . str_replace('.webp', '', $path);
        $fullOriginalPath = Storage::disk('public')->path($originalPath);


        // 如果原图不存在，直接返回占位图片
        if (!file_exists($fullOriginalPath)) {
            return response()->file(public_path('images/no-cover.svg'));
        }

        // 生成缩略图（路径相对于 public 目录）
        if (ImageService::convertToWebP($fullOriginalPath, $fullOptimizedPath)) {
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

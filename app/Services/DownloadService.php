<?php

namespace App\Services;

use App\Models\DownloadLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DownloadService
{
    /**
     * 检查用户今日下载次数是否超过限制
     */
    public function checkDownloadLimit(int $userId): bool
    {
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');

        $downloadCount = DownloadLog::where('user_id', $userId)
            ->whereBetween('download_time', [$todayStart, $todayEnd])
            ->count();

        return $downloadCount < 5;
    }

    /**
     * 尝试记录下载日志（原子操作，带锁）
     */
    public function tryLogDownload(int $userId, int $bookId, ?string $ipAddress = null): bool
    {
        if ($ipAddress === null) {
            $ipAddress = request()->ip();
        }

        try {
            return DB::transaction(function () use ($userId, $bookId, $ipAddress) {
                $todayStart = date('Y-m-d 00:00:00');
                $todayEnd = date('Y-m-d 23:59:59');

                // 锁定相关行并检查下载次数
                $downloadCount = DownloadLog::where('user_id', $userId)
                    ->whereBetween('download_time', [$todayStart, $todayEnd])
                    ->lockForUpdate()
                    ->count();

                // 如果已超过限制，返回false
                if ($downloadCount >= 5) {
                    return false;
                }

                // 记录下载日志
                DownloadLog::create([
                    'user_id' => $userId,
                    'book_id' => $bookId,
                    'download_time' => now(),
                    'ip_address' => $ipAddress,
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('下载日志记录失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取用户今日下载次数
     */
    public function getUserDownloadCount(?int $userId = null): int
    {
        if ($userId === null) {
            return 0;
        }

        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');

        return DownloadLog::where('user_id', $userId)
            ->whereBetween('download_time', [$todayStart, $todayEnd])
            ->count();
    }

    /**
     * 检查用户是否在短时间内重复请求下载同一本书
     * 
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @param int $seconds 时间间隔（秒），默认10秒
     * @return bool 如果允许下载返回true，否则返回false
     */
    public function canDownload(int $userId, int $bookId, int $seconds = 10): bool
    {
        $cacheKey = "download_lock:{$userId}:{$bookId}";
        
        // 检查缓存中是否存在下载锁
        if (Cache::has($cacheKey)) {
            return false;
        }
        
        // 设置下载锁，有效期 $seconds 秒
        Cache::put($cacheKey, true, $seconds);
        
        return true;
    }

    /**
     * 清除下载锁（在下载成功后调用，可选）
     * 
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     */
    public function clearDownloadLock(int $userId, int $bookId): void
    {
        $cacheKey = "download_lock:{$userId}:{$bookId}";
        Cache::forget($cacheKey);
    }
}

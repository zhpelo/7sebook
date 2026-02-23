<?php

namespace App\Services;

use App\Models\DownloadLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DownloadService
{
    private const GUEST_COUNT_PREFIX = 'download_count:guest:';

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
    public function tryLogDownload(int $userId, int $bookId, ?string $ipAddress = null, int $maxDailyLimit = 5): bool
    {
        if ($ipAddress === null) {
            $ipAddress = request()->ip();
        }

        try {
            return DB::transaction(function () use ($userId, $bookId, $ipAddress, $maxDailyLimit) {
                $todayStart = date('Y-m-d 00:00:00');
                $todayEnd = date('Y-m-d 23:59:59');

                // 锁定相关行并检查下载次数
                $downloadCount = DownloadLog::where('user_id', $userId)
                    ->whereBetween('download_time', [$todayStart, $todayEnd])
                    ->lockForUpdate()
                    ->count();

                // 如果已超过限制，返回false
                if ($downloadCount >= max(0, $maxDailyLimit)) {
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
     * 检查用户是否在短时间内重复请求下载
     * 
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @param int $seconds 时间间隔（秒），默认30秒
     * @return bool 如果允许下载返回true，否则返回false
     */
    public function canDownload(int $userId, int $bookId, int $seconds = 30): bool
    {
        return $this->canDownloadBySubjectKey("user:{$userId}", $seconds);
    }

    /**
     * 基于主体Key检查短时间下载锁
     */
    public function canDownloadBySubjectKey(string $subjectKey, int $seconds = 30): bool
    {
        // 全局下载锁：30秒内只允许一次下载请求（针对用户）
        $globalLockKey = "download_lock:{$subjectKey}";
        
        // 使用 Cache::add() 原子操作：只在键不存在时设置值
        // 如果键已存在，add() 返回 false，表示锁已存在
        // 如果键不存在，add() 设置值并返回 true，表示成功获取锁
        $lockAcquired = Cache::add($globalLockKey, true, $seconds);
        
        return $lockAcquired;
    }

    /**
     * 清除下载锁（在下载失败时调用，允许用户立即重试）
     * 
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID（保留参数以保持兼容性，但实际使用全局锁）
     */
    public function clearDownloadLock(int $userId, int $bookId): void
    {
        $globalLockKey = "download_lock:user:{$userId}";
        Cache::forget($globalLockKey);
    }

    /**
     * 清除主体下载锁
     */
    public function clearDownloadLockBySubjectKey(string $subjectKey): void
    {
        $globalLockKey = "download_lock:{$subjectKey}";
        Cache::forget($globalLockKey);
    }

    /**
     * 获取游客今日下载次数（同IP）
     */
    public function getGuestDownloadCount(string $ipAddress): int
    {
        $cacheKey = $this->getGuestDailyCacheKey($ipAddress);
        return (int) Cache::get($cacheKey, 0);
    }

    /**
     * 游客下载次数 +1（同IP）
     */
    public function incrementGuestDownloadCount(string $ipAddress): int
    {
        $cacheKey = $this->getGuestDailyCacheKey($ipAddress);
        $expiresAt = now()->endOfDay();

        $current = (int) Cache::get($cacheKey, 0) + 1;
        Cache::put($cacheKey, $current, $expiresAt);

        return $current;
    }

    private function getGuestDailyCacheKey(string $ipAddress): string
    {
        return self::GUEST_COUNT_PREFIX . date('Ymd') . ':' . md5(trim($ipAddress));
    }
}

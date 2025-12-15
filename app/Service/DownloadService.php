<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\Connection;
use App\Helper\NetworkHelper;
use PDO;
use PDOException;

/**
 * 下载服务类
 * 
 * 处理下载限制、下载日志等相关业务逻辑
 */
class DownloadService
{
    private PDO $db;
    private NetworkHelper $networkHelper;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Connection::getInstance();
        $this->networkHelper = new NetworkHelper();
    }

    /**
     * 检查用户今日下载次数是否超过限制
     * 
     * @param int $userId 用户ID
     * @return bool 是否未超过限制
     */
    public function checkDownloadLimit(int $userId): bool
    {
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');

        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM download_logs 
                WHERE user_id = :user_id 
                AND download_time >= :today_start 
                AND download_time <= :today_end
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':today_start' => $todayStart,
                ':today_end' => $todayEnd
            ]);

            $downloadCount = (int)$stmt->fetchColumn();
            return $downloadCount < 5;
        } catch (PDOException $e) {
            error_log('检查下载限制失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 尝试记录下载日志（原子操作，带锁）
     * 
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @param string|null $ipAddress IP地址（可选，仅用于记录日志）
     * @return bool 是否成功
     */
    public function tryLogDownload(int $userId, int $bookId, ?string $ipAddress = null): bool
    {
        if ($ipAddress === null) {
            $ipAddress = $this->networkHelper->getClientIp();
        }

        try {
            $this->db->beginTransaction();

            $todayStart = date('Y-m-d 00:00:00');
            $todayEnd = date('Y-m-d 23:59:59');

            // 锁定相关行并检查下载次数（只检查用户ID）
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM download_logs 
                WHERE user_id = :user_id 
                AND download_time >= :today_start 
                AND download_time <= :today_end
                FOR UPDATE
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':today_start' => $todayStart,
                ':today_end' => $todayEnd
            ]);

            $downloadCount = (int)$stmt->fetchColumn();

            // 如果已超过限制，回滚并返回false
            if ($downloadCount >= 5) {
                $this->db->rollBack();
                return false;
            }

            // 记录下载日志
            $currentTime = date('Y-m-d H:i:s');

            try {
                $stmt = $this->db->prepare("
                    INSERT INTO download_logs (user_id, book_id, download_time, ip_address) 
                    VALUES (:user_id, :book_id, :download_time, :ip_address)
                ");
                $stmt->execute([
                    ':user_id' => $userId,
                    ':book_id' => $bookId,
                    ':download_time' => $currentTime,
                    ':ip_address' => $ipAddress
                ]);
            } catch (PDOException $e) {
                // 如果IP字段不存在，使用旧的插入方式
                if (strpos($e->getMessage(), 'Unknown column') !== false) {
                    $stmt = $this->db->prepare("
                        INSERT INTO download_logs (user_id, book_id, download_time) 
                        VALUES (:user_id, :book_id, :download_time)
                    ");
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':book_id' => $bookId,
                        ':download_time' => $currentTime
                    ]);
                } else {
                    throw $e;
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('下载日志记录失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取用户今日下载次数
     * 
     * @param int|null $userId 用户ID
     * @return int 下载次数
     */
    public function getUserDownloadCount(?int $userId = null): int
    {
        if ($userId === null) {
            return 0;
        }

        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');

        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM download_logs 
                WHERE user_id = :user_id 
                AND download_time >= :today_start 
                AND download_time <= :today_end
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':today_start' => $todayStart,
                ':today_end' => $todayEnd
            ]);

            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('获取用户下载次数失败: ' . $e->getMessage());
            return 0;
        }
    }
}

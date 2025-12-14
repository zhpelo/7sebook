<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\Connection;
use App\Helper\NetworkHelper;
use PDO;
use PDOException;

/**
 * 搜索服务类
 * 
 * 处理搜索历史、热门搜索等相关业务逻辑
 */
class SearchService
{
    private PDO $db;
    private NetworkHelper $networkHelper;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Connection::getInstance();
        $this->networkHelper = new NetworkHelper();
    }

    /**
     * 记录搜索历史
     * 
     * @param string $query 搜索关键词
     * @param string $type 搜索类型
     * @param int $resultsCount 结果数量
     * @param int|null $userId 用户ID
     * @param string|null $ipAddress IP地址
     * @return bool 是否成功
     */
    public function logSearchHistory(
        string $query,
        string $type = 'all',
        int $resultsCount = 0,
        ?int $userId = null,
        ?string $ipAddress = null
    ): bool {
        if (empty($query)) {
            return false;
        }

        if ($ipAddress === null) {
            $ipAddress = $this->networkHelper->getClientIp();
        }

        try {
            // 检查是否有ip_address字段
            $stmt = $this->db->prepare("
                INSERT INTO search_history 
                (user_id, search_query, search_type, results_count, search_time, ip_address) 
                VALUES (?, ?, ?, ?, NOW(), ?)
            ");
            return $stmt->execute([$userId, $query, $type, $resultsCount, $ipAddress]);
        } catch (PDOException $e) {
            // 如果ip_address字段不存在，使用旧的方式
            if (strpos($e->getMessage(), 'Unknown column') !== false) {
                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO search_history 
                        (user_id, search_query, search_type, results_count, search_time) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    return $stmt->execute([$userId, $query, $type, $resultsCount]);
                } catch (PDOException $e2) {
                    error_log('记录搜索历史失败: ' . $e2->getMessage());
                    return false;
                }
            }
            error_log('记录搜索历史失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取热门搜索关键词
     * 
     * @param int $limit 返回数量
     * @return array<int, array<string, mixed>>
     */
    public function getPopularSearches(int $limit = 20): array
    {
        $limit = max(1, $limit);

        try {
            $sql = "
                SELECT 
                    search_query,
                    COUNT(*) as search_count,
                    COUNT(DISTINCT user_id) as user_count,
                    COUNT(DISTINCT ip_address) as ip_count
                FROM search_history
                WHERE search_query != ''
                GROUP BY search_query
                ORDER BY search_count DESC
                LIMIT :limit
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('获取热门搜索失败: ' . $e->getMessage());
            return [];
        }
    }
}

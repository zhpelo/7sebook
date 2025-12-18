<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\Connection;
use PDO;
use PDOException;

/**
 * 书籍服务类
 * 
 * 处理书籍相关的业务逻辑
 */
class BookService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Connection::getInstance();
    }

    /**
     * 获取下载排行榜
     * 
     * @param int $limit 返回数量
     * @return array<int, array<string, mixed>>
     */
    public function getDownloadRanking(int $limit = 20): array
    {
        $limit = max(1, $limit);

        try {
            $sql = "
                SELECT 
                    b.*,
                    dl_stats.download_count
                FROM (
                    SELECT 
                        book_id,
                        COUNT(*) as download_count
                    FROM download_logs
                    GROUP BY book_id
                    ORDER BY download_count DESC
                    LIMIT :limit
                ) dl_stats
                INNER JOIN books b ON b.id = dl_stats.book_id
                ORDER BY dl_stats.download_count DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('获取下载排行失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取随机书籍（优化版本，适用于百万级数据）
     * 
     * @param int $limit 返回数量
     * @return array<int, array<string, mixed>>
     */
    public function getRandomBooks(int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));

        try {
            // 获取有封面书籍的统计信息
            $statsSql = "
                SELECT 
                    MIN(id) as min_id,
                    MAX(id) as max_id,
                    COUNT(*) as total_count
                FROM books
                WHERE cover_url IS NOT NULL 
                AND cover_url != ''
                AND cover_url != 'null'
            ";
            $stmt = $this->db->query($statsSql);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$stats || $stats['total_count'] == 0) {
                return [];
            }

            $minId = (int)$stats['min_id'];
            $maxId = (int)$stats['max_id'];
            $totalCount = (int)$stats['total_count'];

            // 如果总数小于需要的数量，直接返回全部
            if ($totalCount <= $limit) {
                $sql = "
                    SELECT * FROM books
                    WHERE cover_url IS NOT NULL 
                    AND cover_url != ''
                    AND cover_url != 'null'
                    ORDER BY id DESC
                    LIMIT :limit
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // 生成随机ID
            $randomIds = [];
            for ($i = 0; $i < $limit * 3 && count($randomIds) < $limit * 2; $i++) {
                $randomId = mt_rand($minId, $maxId);
                $randomIds[$randomId] = true;
            }

            $idList = array_keys($randomIds);

            if (empty($idList)) {
                return [];
            }

            // 使用IN查询
            $placeholders = implode(',', array_fill(0, count($idList), '?'));
            $sql = "
                SELECT * FROM books
                WHERE id IN ($placeholders)
                AND cover_url IS NOT NULL 
                AND cover_url != ''
                AND cover_url != 'null'
                LIMIT :limit
            ";

            $stmt = $this->db->prepare($sql);
            foreach ($idList as $index => $id) {
                $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 如果数量不足，使用随机排序补充
            if (count($books) < $limit) {
                $remaining = $limit - count($books);
                $existingIds = array_column($books, 'id');

                if (!empty($existingIds)) {
                    $existingPlaceholders = implode(',', array_fill(0, count($existingIds), '?'));
                    $supplementSql = "
                        SELECT * FROM books
                        WHERE id NOT IN ($existingPlaceholders)
                        AND cover_url IS NOT NULL 
                        AND cover_url != ''
                        AND cover_url != 'null'
                        ORDER BY RAND()
                        LIMIT :limit
                    ";

                    $supplementStmt = $this->db->prepare($supplementSql);
                    foreach ($existingIds as $index => $id) {
                        $supplementStmt->bindValue($index + 1, $id, PDO::PARAM_INT);
                    }
                    $supplementStmt->bindValue(':limit', $remaining, PDO::PARAM_INT);
                    $supplementStmt->execute();

                    $supplementBooks = $supplementStmt->fetchAll(PDO::FETCH_ASSOC);
                    $books = array_merge($books, $supplementBooks);
                }
            }

            shuffle($books);

            return array_slice($books, 0, $limit);
        } catch (PDOException $e) {
            error_log('获取随机书籍失败: ' . $e->getMessage());
            // 回退到简单方法
            try {
                $sql = "
                    SELECT * FROM books
                    WHERE cover_url IS NOT NULL 
                    AND cover_url != ''
                    AND cover_url != 'null'
                    ORDER BY RAND()
                    LIMIT :limit
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e2) {
                error_log('获取随机书籍回退方法也失败: ' . $e2->getMessage());
                return [];
            }
        }
    }

    /**
     * 获取最新书籍
     * 
     * @param int $limit 返回数量
     * @param int $offset 偏移量
     * @return array<int, array<string, mixed>>
     */
    public function getLatestBooks(int $limit = 20, int $offset = 0): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        try {
            $sql = "
                SELECT * FROM books
                ORDER BY id DESC
                LIMIT :limit OFFSET :offset
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('获取最新书籍失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取书籍总数
     * 
     * @return int
     */
    public function getTotalCount(): int
    {
        try {
            $stmt = $this->db->query('SELECT COUNT(*) FROM books');
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('获取书籍总数失败: ' . $e->getMessage());
            return 0;
        }
    }
}

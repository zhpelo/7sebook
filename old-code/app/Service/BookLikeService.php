<?php

declare(strict_types=1);

namespace App\Service;

use App\Database\Connection;
use PDO;
use PDOException;

/**
 * 书籍点赞和收藏服务类
 */
class BookLikeService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Connection::getInstance();
    }

    /**
     * 检查用户是否已点赞某本书
     * 
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @return bool 是否已点赞
     */
    public function isBookLiked(int $userId, int $bookId): bool
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT 1 FROM book_likes WHERE user_id = ? AND book_id = ? LIMIT 1"
            );
            $stmt->execute([$userId, $bookId]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            error_log('检查点赞状态失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 添加点赞
     * 
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @return array{success: bool, message: string}
     */
    public function addLike(int $userId, int $bookId): array
    {
        if ($this->isBookLiked($userId, $bookId)) {
            return ['success' => false, 'message' => '您已经点赞过这本书了'];
        }

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO book_likes (user_id, book_id) VALUES (?, ?)"
            );
            $stmt->execute([$userId, $bookId]);
            return ['success' => true, 'message' => '点赞成功'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['success' => false, 'message' => '您已经点赞过这本书了'];
            }
            error_log('添加点赞失败: ' . $e->getMessage());
            return ['success' => false, 'message' => '操作失败，请稍后重试'];
        }
    }

    /**
     * 取消点赞
     * 
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @return array{success: bool, message: string}
     */
    public function removeLike(int $userId, int $bookId): array
    {
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM book_likes WHERE user_id = ? AND book_id = ?"
            );
            $stmt->execute([$userId, $bookId]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => '已取消点赞'];
            } else {
                return ['success' => false, 'message' => '您还未点赞过这本书'];
            }
        } catch (PDOException $e) {
            error_log('取消点赞失败: ' . $e->getMessage());
            return ['success' => false, 'message' => '操作失败，请稍后重试'];
        }
    }

    /**
     * 获取书籍的点赞数量
     * 
     * @param int $bookId 书籍ID
     * @return int 点赞数量
     */
    public function getBookLikeCount(int $bookId): int
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM book_likes WHERE book_id = ?"
            );
            $stmt->execute([$bookId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('获取点赞数量失败: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 检查用户是否已收藏某本书
     * 
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @return bool 是否已收藏
     */
    public function isBookFavorited(int $userId, int $bookId): bool
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT 1 FROM book_favorites WHERE user_id = ? AND book_id = ? LIMIT 1"
            );
            $stmt->execute([$userId, $bookId]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            error_log('检查收藏状态失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 添加收藏
     * 
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @return array{success: bool, message: string}
     */
    public function addFavorite(int $userId, int $bookId): array
    {
        if ($this->isBookFavorited($userId, $bookId)) {
            return ['success' => false, 'message' => '您已经收藏过这本书了'];
        }

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO book_favorites (user_id, book_id) VALUES (?, ?)"
            );
            $stmt->execute([$userId, $bookId]);
            return ['success' => true, 'message' => '收藏成功'];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return ['success' => false, 'message' => '您已经收藏过这本书了'];
            }
            error_log('添加收藏失败: ' . $e->getMessage());
            return ['success' => false, 'message' => '操作失败，请稍后重试'];
        }
    }

    /**
     * 取消收藏
     * 
     * @param int $userId 用户ID
     * @param int $bookId 书籍ID
     * @return array{success: bool, message: string}
     */
    public function removeFavorite(int $userId, int $bookId): array
    {
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM book_favorites WHERE user_id = ? AND book_id = ?"
            );
            $stmt->execute([$userId, $bookId]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => '已取消收藏'];
            } else {
                return ['success' => false, 'message' => '您还未收藏过这本书'];
            }
        } catch (PDOException $e) {
            error_log('取消收藏失败: ' . $e->getMessage());
            return ['success' => false, 'message' => '操作失败，请稍后重试'];
        }
    }

    /**
     * 获取书籍的收藏数量
     * 
     * @param int $bookId 书籍ID
     * @return int 收藏数量
     */
    public function getBookFavoriteCount(int $bookId): int
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM book_favorites WHERE book_id = ?"
            );
            $stmt->execute([$bookId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('获取收藏数量失败: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 获取用户的点赞记录
     * 
     * @param int $userId 用户ID
     * @param int $limit 返回数量
     * @param int $offset 偏移量
     * @return array<int, array<string, mixed>>
     */
    public function getUserLikedBooks(int $userId, int $limit = 20, int $offset = 0): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT b.*, bl.created_at as liked_at
                FROM book_likes bl
                INNER JOIN books b ON b.id = bl.book_id
                WHERE bl.user_id = :user_id
                ORDER BY bl.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('获取用户点赞记录失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取用户的收藏记录
     * 
     * @param int $userId 用户ID
     * @param int $limit 返回数量
     * @param int $offset 偏移量
     * @return array<int, array<string, mixed>>
     */
    public function getUserFavoritedBooks(int $userId, int $limit = 20, int $offset = 0): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT b.*, bf.created_at as favorited_at
                FROM book_favorites bf
                INNER JOIN books b ON b.id = bf.book_id
                WHERE bf.user_id = :user_id
                ORDER BY bf.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('获取用户收藏记录失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 获取用户点赞记录总数
     * 
     * @param int $userId 用户ID
     * @return int 总数
     */
    public function getUserLikedBooksCount(int $userId): int
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM book_likes WHERE user_id = ?"
            );
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('获取用户点赞记录总数失败: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 获取用户收藏记录总数
     * 
     * @param int $userId 用户ID
     * @return int 总数
     */
    public function getUserFavoritedBooksCount(int $userId): int
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM book_favorites WHERE user_id = ?"
            );
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('获取用户收藏记录总数失败: ' . $e->getMessage());
            return 0;
        }
    }
}

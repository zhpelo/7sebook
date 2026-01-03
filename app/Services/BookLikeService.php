<?php

namespace App\Services;

use App\Models\BookLike;
use App\Models\BookFavorite;
use App\Models\User;
use App\Models\Book;

class BookLikeService
{
    /**
     * 检查用户是否已点赞某本书
     */
    public function isBookLiked(int $userId, int $bookId): bool
    {
        return BookLike::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->exists();
    }

    /**
     * 添加点赞
     */
    public function addLike(int $userId, int $bookId): array
    {
        if ($this->isBookLiked($userId, $bookId)) {
            return ['success' => false, 'message' => '您已经点赞过这本书了'];
        }

        try {
            BookLike::create([
                'user_id' => $userId,
                'book_id' => $bookId,
            ]);
            return ['success' => true, 'message' => '点赞成功'];
        } catch (\Exception $e) {
            if ($e->getCode() == 23000) {
                return ['success' => false, 'message' => '您已经点赞过这本书了'];
            }
            \Log::error('添加点赞失败: ' . $e->getMessage());
            return ['success' => false, 'message' => '操作失败，请稍后重试'];
        }
    }

    /**
     * 取消点赞
     */
    public function removeLike(int $userId, int $bookId): array
    {
        $deleted = BookLike::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->delete();

        if ($deleted > 0) {
            return ['success' => true, 'message' => '已取消点赞'];
        } else {
            return ['success' => false, 'message' => '您还未点赞过这本书'];
        }
    }

    /**
     * 获取书籍的点赞数量
     */
    public function getBookLikeCount(int $bookId): int
    {
        return BookLike::where('book_id', $bookId)->count();
    }

    /**
     * 检查用户是否已收藏某本书
     */
    public function isBookFavorited(int $userId, int $bookId): bool
    {
        return BookFavorite::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->exists();
    }

    /**
     * 添加收藏
     */
    public function addFavorite(int $userId, int $bookId): array
    {
        if ($this->isBookFavorited($userId, $bookId)) {
            return ['success' => false, 'message' => '您已经收藏过这本书了'];
        }

        try {
            BookFavorite::create([
                'user_id' => $userId,
                'book_id' => $bookId,
            ]);
            return ['success' => true, 'message' => '收藏成功'];
        } catch (\Exception $e) {
            if ($e->getCode() == 23000) {
                return ['success' => false, 'message' => '您已经收藏过这本书了'];
            }
            \Log::error('添加收藏失败: ' . $e->getMessage());
            return ['success' => false, 'message' => '操作失败，请稍后重试'];
        }
    }

    /**
     * 取消收藏
     */
    public function removeFavorite(int $userId, int $bookId): array
    {
        $deleted = BookFavorite::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->delete();

        if ($deleted > 0) {
            return ['success' => true, 'message' => '已取消收藏'];
        } else {
            return ['success' => false, 'message' => '您还未收藏过这本书'];
        }
    }

    /**
     * 获取书籍的收藏数量
     */
    public function getBookFavoriteCount(int $bookId): int
    {
        return BookFavorite::where('book_id', $bookId)->count();
    }

    /**
     * 获取用户的点赞记录
     */
    public function getUserLikedBooks(int $userId, int $limit = 20, int $offset = 0): array
    {
        return BookLike::where('user_id', $userId)
            ->with('book')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function ($like) {
                $book = $like->book->toArray();
                $book['liked_at'] = $like->created_at;
                return $book;
            })
            ->toArray();
    }

    /**
     * 获取用户的收藏记录
     */
    public function getUserFavoritedBooks(int $userId, int $limit = 20, int $offset = 0): array
    {
        return BookFavorite::where('user_id', $userId)
            ->with('book')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function ($favorite) {
                $book = $favorite->book->toArray();
                $book['favorited_at'] = $favorite->created_at;
                return $book;
            })
            ->toArray();
    }

    /**
     * 获取用户点赞记录总数
     */
    public function getUserLikedBooksCount(int $userId): int
    {
        return BookLike::where('user_id', $userId)->count();
    }

    /**
     * 获取用户收藏记录总数
     */
    public function getUserFavoritedBooksCount(int $userId): int
    {
        return BookFavorite::where('user_id', $userId)->count();
    }
}

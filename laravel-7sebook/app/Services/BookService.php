<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class BookService
{
    /**
     * 获取下载排行榜
     */
    public function getDownloadRanking(int $limit = 20): array
    {
        $limit = max(1, $limit);

        return DB::table('download_logs')
            ->select([
                'books.*',
                DB::raw('COUNT(download_logs.id) as download_count')
            ])
            ->join('books', 'books.id', '=', 'download_logs.book_id')
            ->groupBy('download_logs.book_id')
            ->orderByDesc('download_count')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return (array) $item;
            })
            ->toArray();
    }

    /**
     * 获取随机书籍（优化版本，适用于百万级数据）
     * 
     * @param int $limit 获取数量
     * @param int $cacheMinutes 缓存时间（分钟），默认60分钟
     * @return array
     */
    public function getRandomBooks(int $limit = 50, int $cacheMinutes = 60): array
    {
        $limit = max(1, min(100, $limit));
        $cacheMinutes = max(1, $cacheMinutes);

        // 生成缓存键，包含 limit 参数以确保不同 limit 有独立的缓存
        $cacheKey = "random_books_{$limit}";

        // 使用缓存，如果缓存不存在则执行查询并缓存结果
        return Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($limit) {
            // 获取有封面书籍的统计信息
            $stats = DB::table('books')
                ->whereNotNull('cover_url')
                ->where('cover_url', '!=', '')
                ->where('cover_url', '!=', 'null')
                ->selectRaw('MIN(id) as min_id, MAX(id) as max_id, COUNT(*) as total_count')
                ->first();

            if (!$stats || $stats->total_count == 0) {
                return [];
            }

            $minId = (int) $stats->min_id;
            $maxId = (int) $stats->max_id;
            $totalCount = (int) $stats->total_count;

            // 如果总数小于需要的数量，直接返回全部
            if ($totalCount <= $limit) {
                return Book::whereNotNull('cover_url')
                    ->where('cover_url', '!=', '')
                    ->where('cover_url', '!=', 'null')
                    ->orderByDesc('id')
                    ->limit($limit)
                    ->get()
                    ->toArray();
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
            $books = Book::whereIn('id', $idList)
                ->whereNotNull('cover_url')
                ->where('cover_url', '!=', '')
                ->where('cover_url', '!=', 'null')
                ->limit($limit)
                ->get()
                ->toArray();

            // 如果数量不足，使用随机排序补充
            if (count($books) < $limit) {
                $remaining = $limit - count($books);
                $existingIds = array_column($books, 'id');

                $supplementBooks = Book::whereNotIn('id', $existingIds)
                    ->whereNotNull('cover_url')
                    ->where('cover_url', '!=', '')
                    ->where('cover_url', '!=', 'null')
                    ->inRandomOrder()
                    ->limit($remaining)
                    ->get()
                    ->toArray();

                $books = array_merge($books, $supplementBooks);
            }

            shuffle($books);

            return array_slice($books, 0, $limit);
        });
    }

    /**
     * 获取最新书籍
     */
    public function getLatestBooks(int $limit = 20, int $offset = 0): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        return Book::orderByDesc('id')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();
    }

    /**
     * 获取书籍总数
     */
    public function getTotalCount(): int
    {
        return Book::count();
    }
}

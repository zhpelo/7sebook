<?php

namespace App\Services;

use App\Models\SearchHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchService
{
    /**
     * 记录搜索历史
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
            $ipAddress = request()->ip();
        }

        try {
            SearchHistory::create([
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'search_query' => $query,
                'search_type' => $type,
                'results_count' => $resultsCount,
                'search_time' => now(),
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('记录搜索历史失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取热门搜索关键词
     */
    public function getPopularSearches(int $limit = 20): array
    {
        $limit = max(1, $limit);

        return DB::table('search_history')
            ->select([
                'search_query',
                DB::raw('COUNT(*) as search_count'),
                DB::raw('COUNT(DISTINCT user_id) as user_count'),
                DB::raw('COUNT(DISTINCT ip_address) as ip_count')
            ])
            ->where('search_query', '!=', '')
            ->groupBy('search_query')
            ->orderByDesc('search_count')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return (array) $item;
            })
            ->toArray();
    }
}

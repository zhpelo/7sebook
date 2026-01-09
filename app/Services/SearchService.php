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
     * 
     * @param int $limit 返回数量限制
     * @param string $timeRange 时间范围: 'today'(今天), 'week'(本周), 'month'(本月), 'all'(全部)
     * @return array
     */
    public function getPopularSearches(int $limit = 20, string $timeRange = 'today'): array
    {
        $limit = max(1, $limit);

        $query = DB::table('search_history')
            ->select([
                'search_query',
                DB::raw('COUNT(*) as search_count'),
                DB::raw('COUNT(DISTINCT user_id) as user_count'),
                DB::raw('COUNT(DISTINCT ip_address) as ip_count')
            ])
            ->where('search_query', '!=', '');

        // 根据时间范围添加过滤条件
        switch ($timeRange) {
            case 'today':
                $query->whereDate('search_time', today());
                break;
            case 'week':
                $query->whereBetween('search_time', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]);
                break;
            case 'month':
                $query->whereBetween('search_time', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ]);
                break;
            case 'all':
                // 不添加时间过滤，显示全部
                break;
            default:
                // 默认使用今天
                $query->whereDate('search_time', today());
                break;
        }

        return $query
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

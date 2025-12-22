<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchHistoryController extends Controller
{
    protected const LIMIT = 20;

    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * 搜索历史管理
     */
    public function searchHistory(Request $request)
    {
        $searchKeyword = trim($request->get('search', ''));
        $page = max(1, (int) $request->get('page', 1));
        $limit = self::LIMIT;
        $offset = ($page - 1) * $limit;

        $query = SearchHistory::with('user');

        if (!empty($searchKeyword)) {
            $query->where('search_query', 'like', "%{$searchKeyword}%");
        }

        // 先计算总数，再做分页
        $totalSearchRecords = (clone $query)->count();

        $searchRecords = $query->orderByDesc('search_time')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $totalPages = (int) ceil($totalSearchRecords / $limit);

        // 获取热门搜索
        $popularSearches = SearchHistory::select(
                'search_query',
                DB::raw('COUNT(*) as search_count'),
                DB::raw('COUNT(DISTINCT user_id) as user_count'),
                DB::raw('COUNT(DISTINCT ip_address) as ip_count')
            )
            ->where('search_query', '!=', '')
            ->groupBy('search_query')
            ->orderByDesc('search_count')
            ->limit(20)
            ->get();

        return view('admin.search_history', [
            'action' => 'search_history',
            'pageTitle' => '搜索记录 - 七秒电子书后台管理',
            'search_records' => $searchRecords,
            'search_keyword' => $searchKeyword,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_search_records' => $totalSearchRecords,
            'limit' => $limit,
            'popular_searches' => $popularSearches,
        ]);
    }
}


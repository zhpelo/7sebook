<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\BookService;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    protected BookService $bookService;
    protected SearchService $searchService;

    public function __construct(
        BookService $bookService,
        SearchService $searchService
    ) {
        $this->bookService = $bookService;
        $this->searchService = $searchService;
    }

    /**
     * 首页/搜索页
     */
    public function index(Request $request)
    {
        $query = trim($request->get('q', ''));
        $type = $request->get('type', 'all');
        $format = $request->get('format', '');
        $page = max(1, (int) $request->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $bookLayout = $request->cookie('book_layout', 'grid');

        $books = [];
        $total = 0;
        $popularSearches = [];

        if (!empty($query)) {
            $queryBuilder = Book::query();

            // 构建搜索条件
            if ($type === 'all') {
                $queryBuilder->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                      ->orWhere('author', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%");
                });
            } elseif ($type === 'title') {
                $queryBuilder->where('title', 'like', "%{$query}%");
            } elseif ($type === 'author') {
                $queryBuilder->where('author', 'like', "%{$query}%");
            } elseif ($type === 'description') {
                $queryBuilder->where('description', 'like', "%{$query}%");
            }

            if (!empty($format)) {
                $queryBuilder->where('file_format', $format);
            }

            $books = $queryBuilder->orderByDesc('id')
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->toArray();

            $total = $queryBuilder->count();

            // 记录搜索历史（只在第一页时记录）
            if ($page === 1) {
                $userId = Auth::check() ? Auth::id() : null;
                $this->searchService->logSearchHistory($query, $type, $total, $userId, $request->ip());
            }
        } else {
            // 没有搜索关键词时，显示随机50本带封面的书籍
            $books = $this->bookService->getRandomBooks(48);
            $total = count($books);
        }

        // 获取热门搜索关键词（在搜索页面和首页显示）
        if (empty($query)) {
            $popularSearches = $this->searchService->getPopularSearches(10);
        }

        $totalPages = !empty($query) ? ceil($total / $limit) : 1;

        return view('search', [
            'action' => 'search',
            'query' => $query,
            'type' => $type,
            'format' => $format,
            'books' => $books,
            'total' => $total,
            'page' => $page,
            'total_pages' => $totalPages,
            'limit' => $limit,
            'book_layout' => $bookLayout,
            'popular_searches' => $popularSearches,
        ]);
    }

    /**
     * 热门搜索
     */
    public function popularSearches()
    {
        $popularSearchesList = $this->searchService->getPopularSearches(20);

        return view('popular_searches', [
            'action' => 'popular_searches',
            'popular_searches_list' => $popularSearchesList,
        ]);
    }
}

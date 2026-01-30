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
     * 首页：随机书籍 + 热门搜索
     */
    public function home(Request $request)
    {
        $bookLayout = $request->cookie('book_layout', 'grid');
        $books = $this->bookService->getRandomBooks(48);
        $popularSearches = $this->searchService->getPopularSearches(10);

        return view('search', [
            'action' => 'home',
            'query' => '',
            'type' => 'all',
            'format' => '',
            'books' => $books,
            'total' => count($books),
            'page' => 1,
            'total_pages' => 1,
            'limit' => 20,
            'book_layout' => $bookLayout,
            'popular_searches' => $popularSearches,
        ]);
    }

    /**
     * 搜索页：关键词搜索、分页、记录历史
     */
    public function search(Request $request)
    {
        $query = trim($request->get('q', ''));
        $type = $request->get('type', 'all');
        $format = $request->get('format', '');
        $page = max(1, (int) $request->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $bookLayout = $request->cookie('book_layout', 'grid');

        // 无关键词时跳转首页
        if (empty($query)) {
            return redirect()->route('home');
        }

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

        $total = $queryBuilder->count();
        $books = (clone $queryBuilder)
            ->orderByDesc('id')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();

        // 记录搜索历史（只在第一页时记录）
        if ($page === 1) {
            $this->searchService->logSearchHistory(
                $query,
                $type,
                $total,
                Auth::id(),
                $request->ip()
            );
        }

        $totalPages = (int) ceil($total / $limit);

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
            'popular_searches' => [],
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

<?php

namespace App\Http\Controllers;

use App\Services\BookService;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    protected BookService $bookService;

    public function __construct(BookService $bookService)
    {
        $this->bookService = $bookService;
    }

    /**
     * 下载排行
     */
    public function downloadRanking(Request $request)
    {
        $downloadRankingBooks = $this->bookService->getDownloadRanking();
        $bookLayout = $request->cookie('book_layout', 'grid');

        return view('download_ranking', [
            'action' => 'download_ranking',
            'download_ranking_books' => $downloadRankingBooks,
            'book_layout' => $bookLayout,
        ]);
    }

    /**
     * 最近更新
     */
    public function latest(Request $request)
    {
        $page = max(1, (int) $request->get('page', 1));
        $limit = 24;
        $offset = ($page - 1) * $limit;
        $bookLayout = $request->cookie('book_layout', 'grid');

        $latestBooksTotal = $this->bookService->getTotalCount();
        $latestBooks = $this->bookService->getLatestBooks($limit, $offset);
        $totalLatestPages = ceil($latestBooksTotal / $limit);

        return view('latest', [
            'action' => 'latest',
            'latest_books' => $latestBooks,
            'latest_books_total' => $latestBooksTotal,
            'page' => $page,
            'total_latest_pages' => $totalLatestPages,
            'book_layout' => $bookLayout,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\DownloadLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DownloadController extends Controller
{
    protected const LIMIT = 20;

    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * 下载记录管理
     */
    public function downloads(Request $request)
    {
        $page = max(1, (int) $request->get('page', 1));
        $limit = self::LIMIT;
        $offset = ($page - 1) * $limit;

        $downloads = DownloadLog::with(['user', 'book'])
            ->orderByDesc('download_time')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $totalDownloads = DownloadLog::count();
        $totalPages = (int) ceil($totalDownloads / $limit);

        // 获取近28天下载趋势数据
        $downloadTrendData = DownloadLog::selectRaw('DATE(download_time) as date, COUNT(*) as download_count')
            ->where('download_time', '>=', now()->subDays(28))
            ->groupBy(DB::raw('DATE(download_time)'))
            ->orderBy('date')
            ->get();

        $dailyDownloadCounts = [];
        $downloadDates = [];
        for ($i = 27; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $downloadDates[] = $date;
            $dailyDownloadCounts[$date] = 0;
        }

        foreach ($downloadTrendData as $data) {
            $dailyDownloadCounts[$data->date] = (int) $data->download_count;
        }

        // 获取下载最多的10本书
        $topBooks = Book::select('books.id', 'books.title', 'books.author', DB::raw('COUNT(download_logs.id) as download_count'))
            ->join('download_logs', 'books.id', '=', 'download_logs.book_id')
            ->groupBy('books.id', 'books.title', 'books.author')
            ->orderByDesc('download_count')
            ->limit(10)
            ->get();

        return view('admin.downloads', [
            'action' => 'downloads',
            'pageTitle' => '下载记录 - 七秒电子书后台管理',
            'downloads' => $downloads,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_downloads' => $totalDownloads,
            'limit' => $limit,
            'download_dates' => $downloadDates,
            'daily_download_counts' => $dailyDownloadCounts,
            'top_books' => $topBooks,
        ]);
    }
}


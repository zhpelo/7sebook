<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\DownloadLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * 后台仪表盘
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_books' => Book::count(),
            'total_downloads' => DownloadLog::count(),
            'today_downloads' => DownloadLog::whereDate('download_time', today())->count(),
        ];

        // 获取近7天用户增长数据
        $userGrowthData = User::selectRaw('DATE(created_at) as date, COUNT(*) as user_count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $dates = [];
        $dailyUserCounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dates[] = $date;
            $dailyUserCounts[$date] = 0;
        }

        foreach ($userGrowthData as $data) {
            $dailyUserCounts[$data->date] = (int) $data->user_count;
        }

        // 获取近7天电子书增长数据
        $bookGrowthData = Book::selectRaw('DATE(created_at) as date, COUNT(*) as book_count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $dailyBookCounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dailyBookCounts[$date] = 0;
        }

        foreach ($bookGrowthData as $data) {
            $dailyBookCounts[$data->date] = (int) $data->book_count;
        }

        // 获取系统信息
        $systemInfo = [
            'server_os' => PHP_OS . ' ' . (php_uname('r') ?? ''),
            'mysql_version' => $this->getMySQLVersion(),
            'timezone' => config('app.timezone') . ' (' . date_default_timezone_get() . ')',
            'debug_mode' => config('app.debug') ? '开启' : '关闭',
        ];

        return view('admin.dashboard', [
            'action' => 'dashboard',
            'pageTitle' => '仪表盘 - 七秒电子书后台管理',
            'stats' => $stats,
            'dates' => $dates,
            'daily_user_counts' => $dailyUserCounts,
            'daily_book_counts' => $dailyBookCounts,
            'system_info' => $systemInfo,
        ]);
    }

    /**
     * 获取MySQL版本
     */
    private function getMySQLVersion()
    {
        try {
            $version = DB::selectOne('SELECT VERSION() as version');
            return $version->version ?? '未知';
        } catch (\Exception $e) {
            return '未知';
        }
    }
}


<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Illuminate\Http\Request;

class EmailLogController extends Controller
{
    protected const LIMIT = 20;

    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * 邮件发送记录管理
     */
    public function emailLogs(Request $request)
    {
        $searchEmail = trim($request->get('search', ''));
        $statusFilter = $request->get('status', '');
        $typeFilter = $request->get('type', '');
        $page = max(1, (int) $request->get('page', 1));
        $limit = self::LIMIT;
        $offset = ($page - 1) * $limit;

        $query = EmailLog::query();

        // 搜索邮箱
        if (!empty($searchEmail)) {
            $query->where('to_email', 'like', "%{$searchEmail}%");
        }

        // 状态筛选
        if (!empty($statusFilter) && in_array($statusFilter, [EmailLog::STATUS_PENDING, EmailLog::STATUS_SENT, EmailLog::STATUS_FAILED])) {
            $query->where('status', $statusFilter);
        }

        // 类型筛选
        if (!empty($typeFilter)) {
            $query->where('type', $typeFilter);
        }

        // 先计算总数，再做分页
        $totalEmailLogs = (clone $query)->count();

        $emailLogs = $query->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $totalPages = (int) ceil($totalEmailLogs / $limit);

        // 统计信息
        $stats = [
            'total' => EmailLog::count(),
            'sent' => EmailLog::where('status', EmailLog::STATUS_SENT)->count(),
            'failed' => EmailLog::where('status', EmailLog::STATUS_FAILED)->count(),
            'pending' => EmailLog::where('status', EmailLog::STATUS_PENDING)->count(),
        ];

        return view('admin.email_logs', [
            'action' => 'email_logs',
            'pageTitle' => '邮件发送记录 - 七秒电子书后台管理',
            'email_logs' => $emailLogs,
            'search_email' => $searchEmail,
            'status_filter' => $statusFilter,
            'type_filter' => $typeFilter,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_email_logs' => $totalEmailLogs,
            'limit' => $limit,
            'stats' => $stats,
        ]);
    }

    /**
     * 查看邮件详情
     */
    public function viewEmailLog($id)
    {
        $emailLog = EmailLog::findOrFail($id);

        return view('admin.view_email_log', [
            'action' => 'view_email_log',
            'pageTitle' => '邮件详情 - 七秒电子书后台管理',
            'email_log' => $emailLog,
        ]);
    }
}

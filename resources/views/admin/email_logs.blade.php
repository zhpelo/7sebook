@extends('admin.layout')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">邮件发送记录</h2>

            <!-- 搜索和筛选表单 -->
            <form method="GET" action="{{ route('admin.email_logs') }}" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-2">
                    <input type="text" name="search" value="{{ $search_email }}" 
                           placeholder="搜索邮箱..." 
                           class="px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-sm shadow-sm text-gray-900 dark:text-gray-100">
                    <select name="status" class="px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-sm shadow-sm text-gray-900 dark:text-gray-100">
                        <option value="">全部状态</option>
                        <option value="pending" {{ $status_filter === 'pending' ? 'selected' : '' }}>待发送</option>
                        <option value="sent" {{ $status_filter === 'sent' ? 'selected' : '' }}>已发送</option>
                        <option value="failed" {{ $status_filter === 'failed' ? 'selected' : '' }}>发送失败</option>
                    </select>
                    <select name="type" class="px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-sm shadow-sm text-gray-900 dark:text-gray-100">
                        <option value="">全部类型</option>
                        <option value="password_reset" {{ $type_filter === 'password_reset' ? 'selected' : '' }}>密码重置</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2.5 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-lg transition-all shadow-md">
                    <i class="fas fa-search mr-2"></i>搜索
                </button>
            </form>

            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">共 {{ $total_email_logs }} 条记录，第 {{ $page }} / {{ $total_pages }} 页</p>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">收件人</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">主题</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">类型</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">状态</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">发送时间</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($email_logs as $log)
                            <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-900 dark:text-gray-100">{{ $log->to_email }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ mb_strlen($log->subject) > 40 ? mb_substr($log->subject, 0, 40) . '...' : $log->subject }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded">
                                        {{ $log->type === 'password_reset' ? '密码重置' : $log->type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($log->status === 'sent')
                                        <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded">
                                            <i class="fas fa-check-circle mr-1"></i>已发送
                                        </span>
                                    @elseif($log->status === 'failed')
                                        <span class="px-2 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded">
                                            <i class="fas fa-times-circle mr-1"></i>发送失败
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded">
                                            <i class="fas fa-clock mr-1"></i>待发送
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $log->sent_at ? $log->sent_at->format('Y-m-d H:i:s') : ($log->created_at->format('Y-m-d H:i:s') . ' (未发送)') }}
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.view_email_log', $log->id) }}" 
                                       class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-sm">
                                        <i class="fas fa-eye mr-1"></i>查看
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($total_pages > 1)
                <div class="flex justify-center items-center space-x-2 mt-6">
                    @if($page > 1)
                        <a href="{{ route('admin.email_logs', array_merge(request()->all(), ['page' => $page - 1])) }}" 
                           class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all text-sm shadow-sm">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    @endif
                    
                    @for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++)
                        <a href="{{ route('admin.email_logs', array_merge(request()->all(), ['page' => $i])) }}" 
                           class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $page == $i ? 'bg-red-600 dark:bg-red-700 text-white shadow-md' : 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }} active:scale-95 shadow-sm">
                            {{ $i }}
                        </a>
                    @endfor
                    
                    @if($page < $total_pages)
                        <a href="{{ route('admin.email_logs', array_merge(request()->all(), ['page' => $page + 1])) }}" 
                           class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all text-sm shadow-sm">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
    <div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">统计信息</h3>
            <div class="space-y-4">
                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="text-sm text-gray-500 dark:text-gray-400">总记录数</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['total'] }}</div>
                </div>
                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div class="text-sm text-gray-500 dark:text-gray-400">已发送</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['sent'] }}</div>
                </div>
                <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <div class="text-sm text-gray-500 dark:text-gray-400">发送失败</div>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['failed'] }}</div>
                </div>
                <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <div class="text-sm text-gray-500 dark:text-gray-400">待发送</div>
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['pending'] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

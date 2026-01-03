@extends('admin.layout')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100">邮件详情</h2>
            <a href="{{ route('admin.email_logs') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-lg transition-all shadow-md text-sm">
                <i class="fas fa-arrow-left mr-2"></i>返回列表
            </a>
        </div>

        <div class="space-y-6">
            <!-- 基本信息 -->
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">基本信息</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-500 dark:text-gray-400">收件人</label>
                        <p class="text-gray-900 dark:text-gray-100 font-medium">{{ $email_log->to_email }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500 dark:text-gray-400">邮件类型</label>
                        <p class="text-gray-900 dark:text-gray-100 font-medium">
                            <span class="px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded">
                                {{ $email_log->type === 'password_reset' ? '密码重置' : $email_log->type }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500 dark:text-gray-400">发送状态</label>
                        <p class="text-gray-900 dark:text-gray-100 font-medium">
                            @if($email_log->status === 'sent')
                                <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded">
                                    <i class="fas fa-check-circle mr-1"></i>已发送
                                </span>
                            @elseif($email_log->status === 'failed')
                                <span class="px-2 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded">
                                    <i class="fas fa-times-circle mr-1"></i>发送失败
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded">
                                    <i class="fas fa-clock mr-1"></i>待发送
                                </span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500 dark:text-gray-400">创建时间</label>
                        <p class="text-gray-900 dark:text-gray-100 font-medium">{{ $email_log->created_at->format('Y-m-d H:i:s') }}</p>
                    </div>
                    @if($email_log->sent_at)
                        <div>
                            <label class="text-sm text-gray-500 dark:text-gray-400">发送时间</label>
                            <p class="text-gray-900 dark:text-gray-100 font-medium">{{ $email_log->sent_at->format('Y-m-d H:i:s') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 邮件主题 -->
            <div>
                <label class="text-sm text-gray-500 dark:text-gray-400 mb-2 block">邮件主题</label>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <p class="text-gray-900 dark:text-gray-100 font-medium">{{ $email_log->subject }}</p>
                </div>
            </div>

            <!-- 邮件内容 -->
            <div>
                <label class="text-sm text-gray-500 dark:text-gray-400 mb-2 block">邮件内容</label>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                    <div class="prose dark:prose-invert max-w-none">
                        {!! $email_log->content !!}
                    </div>
                </div>
            </div>

            <!-- 错误信息（如果有） -->
            @if($email_log->error_message)
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-red-700 dark:text-red-400 mb-2">
                        <i class="fas fa-exclamation-circle mr-2"></i>错误信息
                    </h4>
                    <p class="text-sm text-red-600 dark:text-red-300">{{ $email_log->error_message }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

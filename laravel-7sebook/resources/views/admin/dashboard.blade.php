@extends('admin.layout')

@section('content')
<!-- 统计卡片 -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
    <div class="bg-gradient-to-br from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between mb-2">
            <i class="fas fa-users text-3xl opacity-80"></i>
        </div>
        <div class="text-3xl font-bold mb-1">{{ number_format($stats['total_users']) }}</div>
        <div class="text-sm opacity-90">总用户数</div>
    </div>
    
    <div class="bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between mb-2">
            <i class="fas fa-book text-3xl opacity-80"></i>
        </div>
        <div class="text-3xl font-bold mb-1">{{ number_format($stats['total_books']) }}</div>
        <div class="text-sm opacity-90">电子书数量</div>
    </div>
    
    <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 dark:from-yellow-600 dark:to-yellow-700 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between mb-2">
            <i class="fas fa-download text-3xl opacity-80"></i>
        </div>
        <div class="text-3xl font-bold mb-1">{{ number_format($stats['total_downloads']) }}</div>
        <div class="text-sm opacity-90">总下载次数</div>
    </div>
    
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between mb-2">
            <i class="fas fa-calendar-day text-3xl opacity-80"></i>
        </div>
        <div class="text-3xl font-bold mb-1">{{ number_format($stats['today_downloads']) }}</div>
        <div class="text-sm opacity-90">今日下载</div>
    </div>
</div>

<!-- 图表区域 -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg md:text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 md:mb-6">近7天用户增长</h3>
        <div style="height: 300px;">
            <canvas id="userGrowthChart"></canvas>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg md:text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 md:mb-6">近7天电子书增长</h3>
        <div style="height: 300px;">
            <canvas id="bookGrowthChart"></canvas>
        </div>
    </div>
</div>

<!-- 系统信息 -->
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
    <h3 class="text-lg md:text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 md:mb-6">系统信息</h3>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
        <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
            <i class="fas fa-code text-red-500 dark:text-red-400 text-2xl mr-3"></i>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">PHP版本</p>
                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ PHP_VERSION }}</p>
            </div>
        </div>
        <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
            <i class="fas fa-layer-group text-green-500 dark:text-green-400 text-2xl mr-3"></i>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">框架</p>
                <p class="font-semibold text-gray-900 dark:text-gray-100">Laravel {{ app()->version() }}</p>
            </div>
        </div>
        <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
            <i class="fas fa-server text-blue-500 dark:text-blue-400 text-2xl mr-3"></i>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">服务器系统</p>
                <p class="font-semibold text-gray-900 dark:text-gray-100 text-xs">{{ $system_info['server_os'] ?? '未知' }}</p>
            </div>
        </div>
        <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
            <i class="fas fa-database text-yellow-500 dark:text-yellow-400 text-2xl mr-3"></i>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">MySQL版本</p>
                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $system_info['mysql_version'] ?? '未知' }}</p>
            </div>
        </div>
        <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
            <i class="fas fa-globe text-indigo-500 dark:text-indigo-400 text-2xl mr-3"></i>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">当前时区</p>
                <p class="font-semibold text-gray-900 dark:text-gray-100 text-xs">{{ $system_info['timezone'] ?? '未知' }}</p>
            </div>
        </div>
        <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
            <i class="fas fa-bug {{ ($system_info['debug_mode'] ?? '关闭') === '开启' ? 'text-red-500 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }} text-2xl mr-3"></i>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Debug模式</p>
                <p class="font-semibold {{ ($system_info['debug_mode'] ?? '关闭') === '开启' ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">
                    {{ $system_info['debug_mode'] ?? '未知' }}
                </p>
            </div>
        </div>
        <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
            <i class="fas fa-user-shield text-purple-500 dark:text-purple-400 text-2xl mr-3"></i>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">当前用户</p>
                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ Auth::user()->username ?? '未知' }}</p>
            </div>
        </div>
        <div class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
            <i class="fas fa-clock text-orange-500 dark:text-orange-400 text-2xl mr-3"></i>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">服务器时间</p>
                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ now()->format('Y-m-d H:i:s') }}</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const userCtx = document.getElementById('userGrowthChart');
    if (userCtx) {
        new Chart(userCtx, {
            type: 'line',
            data: {
                labels: @json($dates),
                datasets: [{
                    label: '用户数',
                    data: @json(array_values($daily_user_counts)),
                    borderColor: 'rgb(220, 38, 38)',
                    backgroundColor: 'rgba(220, 38, 38, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    const bookCtx = document.getElementById('bookGrowthChart');
    if (bookCtx) {
        new Chart(bookCtx, {
            type: 'line',
            data: {
                labels: @json($dates),
                datasets: [{
                    label: '电子书数',
                    data: @json(array_values($daily_book_counts)),
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});
</script>
@endpush
@endsection

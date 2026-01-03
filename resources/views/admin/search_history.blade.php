@extends('admin.layout')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">搜索记录</h2>

            <!-- 搜索表单 -->
            <form method="GET" action="{{ route('admin.search_history') }}" class="mb-6">
                <div class="flex gap-2">
                    <input type="text" name="search" value="{{ $search_keyword }}" 
                           placeholder="搜索关键词..." 
                           class="flex-1 px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-sm shadow-sm text-gray-900 dark:text-gray-100">
                    <button type="submit" class="px-4 py-2.5 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-lg transition-all shadow-md">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">共 {{ $total_search_records }} 条记录，第 {{ $page }} / {{ $total_pages }} 页</p>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">搜索关键词</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">搜索类型</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">结果数</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">用户</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">IP地址</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">搜索时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($search_records as $record)
                            <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-3">
                                    <strong class="text-gray-900 dark:text-gray-100">{{ $record->search_query }}</strong>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded">{{ $record->search_type ?? 'all' }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $record->results_count }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $record->user->username ?? '游客' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $record->ip_address }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $record->search_time->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($total_pages > 1)
                <div class="flex justify-center items-center space-x-2 mt-6">
                    @if($page > 1)
                        <a href="{{ route('admin.search_history', array_merge(request()->all(), ['page' => $page - 1])) }}" 
                           class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all text-sm shadow-sm">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    @endif
                    
                    @for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++)
                        <a href="{{ route('admin.search_history', array_merge(request()->all(), ['page' => $i])) }}" 
                           class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $page == $i ? 'bg-red-600 dark:bg-red-700 text-white shadow-md' : 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }} active:scale-95 shadow-sm">
                            {{ $i }}
                        </a>
                    @endfor
                    
                    @if($page < $total_pages)
                        <a href="{{ route('admin.search_history', array_merge(request()->all(), ['page' => $page + 1])) }}" 
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
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">热门搜索TOP20</h3>
            <div class="space-y-3">
                @foreach($popular_searches as $index => $search)
                    <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold {{ $index < 3 ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
                            {{ $index + 1 }}
                        </span>
                        <div class="flex-grow min-w-0">
                            <a href="{{ route('search', ['q' => $search->search_query]) }}" target="_blank" class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 block truncate">
                                {{ $search->search_query }}
                            </a>
                            <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <span>{{ $search->search_count }} 次</span>
                                <span>{{ $search->user_count }} 人</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

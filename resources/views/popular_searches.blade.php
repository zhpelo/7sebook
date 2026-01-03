@extends('layout')

@section('content')
<div class="py-4 md:py-6">
    <h1 class="text-lg md:text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-2">热门搜索</h1>
    <p class="text-sm md:text-base text-gray-500 dark:text-gray-400 mb-4">用户最常搜索的关键词</p>
    
    @if(!empty($popular_searches_list))
        <div class="space-y-3 md:space-y-4 max-w-4xl mx-auto">
            @foreach($popular_searches_list as $index => $search)
                <a href="{{ route('search', ['q' => $search['search_query']]) }}" class="block">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 md:p-5 flex items-center justify-between hover:shadow-lg transition-all border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-4 flex-grow min-w-0">
                            <!-- 排名 -->
                            <div class="flex-shrink-0 w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center font-bold text-base md:text-lg {{ $index < 3 ? 'bg-gradient-to-br from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
                                {{ $index + 1 }}
                            </div>
                            
                            <!-- 关键词 -->
                            <div class="flex-grow min-w-0">
                                <h3 class="font-semibold text-sm md:text-lg text-gray-800 dark:text-gray-200 mb-1">
                                    {{ $search['search_query'] }}
                                </h3>
                                <div class="flex items-center gap-4 md:gap-6 text-xs md:text-sm text-gray-500 dark:text-gray-400">
                                    <span><i class="fas fa-search mr-2"></i>{{ $search['search_count'] }} 次搜索</span>
                                    <span><i class="fas fa-users mr-2"></i>{{ $search['user_count'] }} 人搜索</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 箭头 -->
                        <div class="flex-shrink-0 text-gray-400 dark:text-gray-500 text-lg">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="text-center py-16">
            <i class="fas fa-search text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400">暂无搜索数据</p>
        </div>
    @endif
</div>
@endsection

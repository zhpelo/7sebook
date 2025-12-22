@extends('layout')

@section('content')
<!-- 桌面端搜索表单 -->
<div class="hidden md:block py-6">
    <form method="GET" action="{{ route('search') }}" class="max-w-4xl mx-auto">
        <div class="flex gap-3">
            <input type="text" name="q" value="{{ $query }}" 
                   placeholder="搜索书名、作者或描述..." 
                   class="flex-1 px-5 py-3.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 text-base shadow-sm text-gray-900 dark:text-gray-100">
            <select name="type" class="px-4 py-3.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 text-base shadow-sm text-gray-900 dark:text-gray-100">
                <option value="all" {{ $type === 'all' ? 'selected' : '' }}>全部</option>
                <option value="title" {{ $type === 'title' ? 'selected' : '' }}>书名</option>
                <option value="author" {{ $type === 'author' ? 'selected' : '' }}>作者</option>
                <option value="description" {{ $type === 'description' ? 'selected' : '' }}>描述</option>
            </select>
            <button type="submit" class="px-8 py-3.5 bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white rounded-xl transition-all font-medium shadow-md hover:shadow-lg">
                <i class="fas fa-search mr-2"></i>搜索
            </button>
        </div>
    </form>
</div>

<!-- 搜索结果标题 -->
<div class="py-4 md:py-6">
    @if(!empty($query))
        <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
            搜索结果：<span class="text-red-600 dark:text-red-400">{{ $query }}</span>
            @if($total > 0)
                <span class="text-sm md:text-base text-gray-500 dark:text-gray-400 font-normal">(共 {{ $total }} 本)</span>
            @endif
        </h1>
    @else
        <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">推荐书籍</h1>
        <p class="text-sm md:text-base text-gray-500 dark:text-gray-400">为您精选的优质电子书</p>
    @endif

    <!-- 书籍列表 -->
    @if(!empty($books))
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6  gap-2 md:gap-4 lg:gap-6 mt-4 md:mt-6">
            @foreach($books as $book)
                <a href="{{ route('book.detail', $book['id']) }}" class="group">
                    <div class="book-card bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-sm hover:shadow-lg border border-gray-200 dark:border-gray-700">
                        <div class="aspect-[3/4] bg-gray-100 dark:bg-gray-700 relative overflow-hidden">
                            @if(!empty($book['cover_url']))
                                <img src="{{ \App\Helpers\ImageHelper::getThumbnailUrl($book['cover_url']) }}" 
                                     alt="{{ $book['title'] }}" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                     loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                    <i class="fas fa-book text-3xl"></i>
                                </div>
                            @endif
                            @if(isset($book['file_format']))
                                <span class="absolute top-1 right-1 bg-gray-900 dark:bg-gray-700 bg-opacity-75 text-white text-xs px-1.5 py-0.5 rounded">
                                    {{ strtoupper($book['file_format']) }}
                                </span>
                            @endif
                        </div>
                        <div class="p-2 md:p-3">
                            <h3 class="font-medium text-xs md:text-sm mb-1 line-clamp-2 text-gray-800 dark:text-gray-200 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">
                                {{ $book['title'] }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1">{{ $book['author'] ?: '未知作者' }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- 分页 -->
        @if($total_pages > 1)
            <div class="flex justify-center items-center space-x-2 mt-6 md:mt-8">
                @if($page > 1)
                    <a href="{{ route('search', array_merge(request()->all(), ['page' => $page - 1])) }}" 
                       class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all text-sm shadow-sm">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                @endif
                
                @for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++)
                    <a href="{{ route('search', array_merge(request()->all(), ['page' => $i])) }}" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $page == $i ? 'bg-red-600 dark:bg-red-700 text-white shadow-md' : 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }} active:scale-95 shadow-sm">
                        {{ $i }}
                    </a>
                @endfor
                
                @if($page < $total_pages)
                    <a href="{{ route('search', array_merge(request()->all(), ['page' => $page + 1])) }}" 
                       class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all text-sm shadow-sm">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                @endif
            </div>
        @endif
    @else
        <div class="text-center py-16">
            <i class="fas fa-search text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400">没有找到相关书籍</p>
        </div>
    @endif

    <!-- 热门搜索 -->
    @if(!empty($popular_searches) && empty($query))
        <div class="mt-8 md:mt-12">
            <h2 class="text-base md:text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 md:mb-6">热门搜索</h2>
            <div class="flex flex-wrap gap-2 md:gap-3">
                @foreach($popular_searches as $search)
                    <a href="{{ route('search', ['q' => $search['search_query']]) }}" 
                       class="px-4 py-2 md:px-5 md:py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-red-50 dark:hover:bg-red-900/20 hover:border-red-300 dark:hover:border-red-700 hover:text-red-600 dark:hover:text-red-400 text-sm md:text-base active:scale-95 transition-all shadow-sm hover:shadow-md">
                        {{ $search['search_query'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

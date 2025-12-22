@extends('layout')

@section('content')
<div class="py-4 md:py-6">
    <!-- 标签标题 -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-4 md:p-6 lg:p-8 mb-4 md:mb-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                <i class="fas fa-tags text-red-600 dark:text-red-400 text-2xl md:text-3xl mr-3"></i>
                <div>
                    <h1 class="text-xl md:text-2xl lg:text-3xl font-bold text-gray-900 dark:text-gray-100">
                        标签：<span class="text-red-600 dark:text-red-400">{{ $tag_name }}</span>
                    </h1>
                    <p class="text-sm md:text-base text-gray-500 dark:text-gray-400 mt-1">
                        共 {{ $tag_usage_count }} 本电子书
                    </p>
                </div>
            </div>
            <a href="{{ route('home') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-all text-sm md:text-base">
                <i class="fas fa-arrow-left mr-2"></i>返回首页
            </a>
        </div>
    </div>

    <!-- 书籍列表 -->
    @if($books->count() > 0)
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2 md:gap-4 lg:gap-6">
            @foreach($books as $book)
                <a href="{{ route('book.detail', $book->id) }}" class="group">
                    <div class="book-card bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-sm hover:shadow-lg border border-gray-200 dark:border-gray-700">
                        <div class="aspect-[3/4] bg-gray-100 dark:bg-gray-700 relative overflow-hidden">
                            @if($book->cover_url)
                                <img src="{{ \App\Helpers\ImageHelper::getThumbnailUrl($book->cover_url) }}" 
                                     alt="{{ $book->title }}" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                     loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                    <i class="fas fa-book text-3xl"></i>
                                </div>
                            @endif
                            @if($book->file_format)
                                <span class="absolute top-1 right-1 bg-gray-900 dark:bg-gray-700 bg-opacity-75 text-white text-xs px-1.5 py-0.5 rounded">
                                    {{ strtoupper($book->file_format) }}
                                </span>
                            @endif
                        </div>
                        <div class="p-2 md:p-3">
                            <h3 class="font-medium text-xs md:text-sm mb-1 line-clamp-2 text-gray-800 dark:text-gray-200 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">
                                {{ $book->title }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1">{{ $book->author ?: '未知作者' }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- 分页 -->
        @if($books->hasPages())
            <div class="flex justify-center items-center space-x-2 mt-6 md:mt-8">
                @if($books->onFirstPage())
                    <span class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-600 rounded-lg text-sm cursor-not-allowed">
                        <i class="fas fa-chevron-left"></i>
                    </span>
                @else
                    <a href="{{ $books->previousPageUrl() }}" 
                       class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all text-sm shadow-sm">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                @endif
                
                @foreach($books->getUrlRange(max(1, $books->currentPage() - 2), min($books->lastPage(), $books->currentPage() + 2)) as $page => $url)
                    <a href="{{ $url }}" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $books->currentPage() == $page ? 'bg-red-600 dark:bg-red-700 text-white shadow-md' : 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }} active:scale-95 shadow-sm">
                        {{ $page }}
                    </a>
                @endforeach
                
                @if($books->hasMorePages())
                    <a href="{{ $books->nextPageUrl() }}" 
                       class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all text-sm shadow-sm">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                @else
                    <span class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-600 rounded-lg text-sm cursor-not-allowed">
                        <i class="fas fa-chevron-right"></i>
                    </span>
                @endif
            </div>
        @endif
    @else
        <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-200 dark:border-gray-700">
            <i class="fas fa-book-open text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400">该标签下暂无书籍</p>
        </div>
    @endif
</div>
@endsection

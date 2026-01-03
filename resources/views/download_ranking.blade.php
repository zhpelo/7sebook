@extends('layout')

@section('content')
<div class="py-4 md:py-6">
    <h1 class="text-lg md:text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-2">下载排行</h1>
    <p class="text-sm md:text-base text-gray-500 dark:text-gray-400 mb-4">最受欢迎的电子书</p>
    
    @if(!empty($download_ranking_books))
        <div class="space-y-3 md:space-y-4 max-w-4xl mx-auto">
            @foreach($download_ranking_books as $index => $book)
                <a href="{{ route('book.detail', $book['id']) }}" class="block">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 md:p-5 flex items-center gap-4 hover:shadow-lg transition-all border border-gray-200 dark:border-gray-700">
                        <!-- 排名 -->
                        <div class="flex-shrink-0 w-12 h-12 md:w-14 md:h-14 rounded-full flex items-center justify-center font-bold text-lg md:text-xl {{ $index < 3 ? 'bg-gradient-to-br from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
                            {{ $index + 1 }}
                        </div>
                        
                        <!-- 封面 -->
                        <div class="flex-shrink-0 w-20 h-28 md:w-24 md:h-32 bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden shadow-sm">
                            @if(!empty($book['cover_url']))
                                <img src="{{ \App\Helpers\ImageHelper::getThumbnailUrl($book['cover_url']) }}" 
                                     alt="{{ $book['title'] }}" 
                                     class="w-full h-full object-cover"
                                     loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                    <i class="fas fa-book text-xl"></i>
                                </div>
                            @endif
                        </div>
                        
                        <!-- 信息 -->
                        <div class="flex-grow min-w-0">
                            <h3 class="font-semibold text-sm md:text-lg text-gray-800 dark:text-gray-200 mb-1 line-clamp-1">
                                {{ $book['title'] }}
                            </h3>
                            <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mb-2">{{ $book['author'] ?: '未知作者' }}</p>
                            <div class="flex items-center text-xs md:text-sm text-gray-400 dark:text-gray-500">
                                <i class="fas fa-download mr-2"></i>
                                <span>下载 {{ $book['download_count'] ?? 0 }} 次</span>
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
            <i class="fas fa-trophy text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400">暂无下载数据</p>
        </div>
    @endif
</div>
@endsection

@extends('layout')

@section('content')
<div class="py-4 md:py-6">
    @auth
        <h1 class="text-lg md:text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-2">我的收藏</h1>
        <p class="text-sm md:text-base text-gray-500 dark:text-gray-400 mb-4">共 {{ $user_favorited_total }} 本</p>
        
        @if(!empty($user_favorited_books))
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2 md:gap-4 lg:gap-6">
                @foreach($user_favorited_books as $book)
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
                                <span class="absolute top-1 right-1 bg-yellow-500 dark:bg-yellow-600 text-white text-xs px-1.5 py-0.5 rounded">
                                    <i class="fas fa-star"></i>
                                </span>
                            </div>
                            <div class="p-2">
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
            @if($total_favorited_pages > 1)
                <div class="flex justify-center items-center space-x-2 mt-6 md:mt-8">
                    @if($page > 1)
                        <a href="{{ route('my_favorites', ['page' => $page - 1]) }}" 
                           class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all text-sm shadow-sm">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    @endif
                    
                    @for($i = max(1, $page - 2); $i <= min($total_favorited_pages, $page + 2); $i++)
                        <a href="{{ route('my_favorites', ['page' => $i]) }}" 
                           class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $page == $i ? 'bg-red-600 dark:bg-red-700 text-white shadow-md' : 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }} active:scale-95 shadow-sm">
                            {{ $i }}
                        </a>
                    @endfor
                    
                    @if($page < $total_favorited_pages)
                        <a href="{{ route('my_favorites', ['page' => $page + 1]) }}" 
                           class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all text-sm shadow-sm">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    @endif
                </div>
            @endif
        @else
            <div class="text-center py-16">
                <i class="fas fa-star text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">您还没有收藏任何书籍</p>
            </div>
        @endif
    @else
        <div class="text-center py-16">
            <i class="fas fa-lock text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400 mb-4">请先登录</p>
            <a href="{{ route('login') }}" class="inline-block px-6 py-3 bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white rounded-xl shadow-md hover:shadow-lg transition-all">
                去登录
            </a>
        </div>
    @endauth
</div>
@endsection

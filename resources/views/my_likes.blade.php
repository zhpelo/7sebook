@extends('layout')

@section('content')
<div class="py-4 md:py-6">
    @auth
        <h1 class="text-lg md:text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-2">我的点赞</h1>
        <p class="text-sm md:text-base text-gray-500 dark:text-gray-400 mb-4">共 {{ $user_liked_total }} 本</p>
        
        @if(!empty($user_liked_books))
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2 md:gap-4 lg:gap-6">
                @foreach($user_liked_books as $book)
                    @include('partials.book_card', ['book' => $book, 'badge' => 'like'])
                @endforeach
            </div>

            <!-- 分页 -->
            @if($total_liked_pages > 1)
                <div class="flex justify-center items-center space-x-2 mt-6 md:mt-8">
                    @if($page > 1)
                        <a href="{{ route('my_likes', ['page' => $page - 1]) }}" 
                           class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all text-sm shadow-sm">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    @endif
                    
                    @for($i = max(1, $page - 2); $i <= min($total_liked_pages, $page + 2); $i++)
                        <a href="{{ route('my_likes', ['page' => $i]) }}" 
                           class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $page == $i ? 'bg-red-600 dark:bg-red-700 text-white shadow-md' : 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }} active:scale-95 shadow-sm">
                            {{ $i }}
                        </a>
                    @endfor
                    
                    @if($page < $total_liked_pages)
                        <a href="{{ route('my_likes', ['page' => $page + 1]) }}" 
                           class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all text-sm shadow-sm">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    @endif
                </div>
            @endif
        @else
            <div class="text-center py-16">
                <i class="fas fa-heart text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">您还没有点赞任何书籍</p>
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

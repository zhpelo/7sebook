@extends('layout')

@section('content')
<div class="py-4 md:py-6">
    <h1 class="text-lg md:text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-2">最近更新</h1>
    <p class="text-sm md:text-base text-gray-500 dark:text-gray-400 mb-4">共 {{ $latest_books_total }} 本电子书</p>
    
    @if(!empty($latest_books))
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2 md:gap-4 lg:gap-6">
            @foreach($latest_books as $book)
                @include('partials.book_card', ['book' => $book])
            @endforeach
        </div>

        <!-- 分页 -->
        @if($total_latest_pages > 1)
            <div class="flex justify-center items-center space-x-2 mt-6 md:mt-8">
                @if($page > 1)
                    <a href="{{ route('latest', ['page' => $page - 1]) }}" 
                       class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all text-sm shadow-sm">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                @endif
                
                @for($i = max(1, $page - 2); $i <= min($total_latest_pages, $page + 2); $i++)
                    <a href="{{ route('latest', ['page' => $i]) }}" 
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $page == $i ? 'bg-red-600 dark:bg-red-700 text-white shadow-md' : 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }} active:scale-95 shadow-sm">
                        {{ $i }}
                    </a>
                @endfor
                
                @if($page < $total_latest_pages)
                    <a href="{{ route('latest', ['page' => $page + 1]) }}" 
                       class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all text-sm shadow-sm">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                @endif
            </div>
        @endif
    @else
        <div class="text-center py-16">
            <i class="fas fa-book text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400">暂无电子书</p>
        </div>
    @endif
</div>
@endsection

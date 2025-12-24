{{-- 
    可复用的书籍卡片组件
    参数：
    - $book: 书籍对象或数组，必须包含 id, title, author, cover_url, file_format
    - $route: 可选，链接路由名称，默认为 'book.detail'
    - $routeParam: 可选，路由参数，默认为 $book['id'] 或 $book->id
    - $badge: 可选，右上角徽章，'like' 显示红色心形，'favorite' 显示黄色星形，其他字符串直接显示
--}}
@php
    $bookId = is_array($book) ? ($book['id'] ?? null) : ($book->id ?? null);
    $bookTitle = is_array($book) ? ($book['title'] ?? '') : ($book->title ?? '');
    $bookAuthor = is_array($book) ? ($book['author'] ?? '') : ($book->author ?? '');
    $coverUrl = is_array($book) ? ($book['cover_url'] ?? null) : ($book->cover_url ?? null);
    $fileFormat = is_array($book) ? ($book['file_format'] ?? null) : ($book->file_format ?? null);
    $routeName = $route ?? 'book.detail';
    $routeParam = $routeParam ?? $bookId;
    $badge = $badge ?? null;
@endphp

<a href="{{ route($routeName, $routeParam) }}" class="group">
    <div class="book-card bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-sm hover:shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="aspect-[3/4] bg-gray-100 dark:bg-gray-700 relative overflow-hidden">
            @if(!empty($coverUrl))
                <img src="{{ \App\Helpers\ImageHelper::getThumbnailUrl($coverUrl) }}" 
                     alt="{{ $bookTitle }}" 
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                     loading="lazy">
            @else
                <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                    <i class="fas fa-book text-3xl"></i>
                </div>
            @endif
            {{-- 文件类型徽章 --}}
            @if(!empty($fileFormat))
                <span class="absolute top-1 right-1 bg-gray-900 dark:bg-gray-700 bg-opacity-75 text-white text-xs px-1.5 py-0.5 rounded">
                    {{ strtoupper($fileFormat) }}
                </span>
            @endif
            {{-- 特殊徽章（点赞/收藏）--}}
            @if($badge === 'like')
                <span class="absolute top-1 left-1 bg-red-500 dark:bg-red-600 text-white text-xs px-1.5 py-0.5 rounded">
                    <i class="fas fa-heart"></i>
                </span>
            @elseif($badge === 'favorite')
                <span class="absolute top-1 left-1 bg-yellow-500 dark:bg-yellow-600 text-white text-xs px-1.5 py-0.5 rounded">
                    <i class="fas fa-star"></i>
                </span>
            @endif
        </div>
        <div class="p-2 md:p-3">
            <h3 class="font-medium text-xs md:text-sm mb-1 line-clamp-2 text-gray-800 dark:text-gray-200 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">
                {{ $bookTitle }}
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1">{{ $bookAuthor ?: '未知作者' }}</p>
        </div>
    </div>
</a>

@extends('layout')

@section('meta')
    @if(isset($book))
        <meta property="og:type" content="book">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ $book->title }} - {{ $book->author }}">
        <meta property="og:description" content="{{ mb_substr(strip_tags($book->description ?? '暂无简介'), 0, 200) }}">
        @if($book->cover_url)
            <meta property="og:image" content="{{ Storage::url($book->cover_url) }}">
        @endif
    @endif
@endsection

@section('content')
<div class="py-4 md:py-6">
    @if(isset($book))
        <!-- 书籍信息卡片 -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md overflow-hidden mb-4 md:mb-6 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col md:flex-row">
                <!-- 封面 -->
                <div class="flex-shrink-0 w-full md:w-56 lg:w-64 p-4 md:p-6 lg:p-8 flex justify-center md:justify-start">
                    @if($book->cover_url)
                        <img src="{{ Storage::url($book->cover_url) }}" 
                             alt="{{ $book->title }}" 
                             class="w-40 h-56 md:w-48 md:h-64 lg:w-56 lg:h-80 object-cover rounded-xl shadow-lg">
                    @else
                        <div class="w-40 h-56 md:w-48 md:h-64 lg:w-56 lg:h-80 bg-gray-200 dark:bg-gray-700 flex items-center justify-center rounded-xl">
                            <i class="fas fa-book text-5xl text-gray-400 dark:text-gray-500"></i>
                        </div>
                    @endif
                </div>
                
                <!-- 信息 -->
                <div class="flex-grow p-4 md:p-6 lg:p-8">
                    <h1 class="text-xl md:text-3xl lg:text-4xl font-bold text-gray-900 dark:text-gray-100 mb-3">{{ $book->title }}</h1>
                    <p class="text-base md:text-lg text-gray-600 dark:text-gray-400 mb-6">
                        <i class="fas fa-user text-gray-400 dark:text-gray-500 mr-2"></i>{{ $book->author ?? '未知作者' }}
                    </p>
                    
                    <!-- 操作按钮 -->
                    <div class="flex flex-wrap gap-3 mb-6">
                        @auth
                            <a href="{{ route('book.download', $book->id) }}" 
                               class="px-6 md:px-8 py-3 md:py-3.5 bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white rounded-xl transition-all font-medium text-base shadow-md hover:shadow-lg">
                                <i class="fas fa-download mr-2"></i>下载
                            </a>
                            <button class="btn-like px-5 md:px-6 py-3 md:py-3.5 rounded-xl font-medium active:scale-95 transition-all {{ $book->is_liked ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }} hover:shadow-md" 
                                    data-book-id="{{ $book->id }}" 
                                    data-liked="{{ $book->is_liked ? '1' : '0' }}">
                                <i class="fas fa-heart mr-2"></i>
                                <span class="like-count">{{ $book->like_count }}</span>
                            </button>
                            <button class="btn-favorite px-5 md:px-6 py-3 md:py-3.5 rounded-xl font-medium active:scale-95 transition-all {{ $book->is_favorited ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }} hover:shadow-md" 
                                    data-book-id="{{ $book->id }}" 
                                    data-favorited="{{ $book->is_favorited ? '1' : '0' }}">
                                <i class="fas fa-star mr-2"></i>
                                <span class="favorite-count">{{ $book->favorite_count }}</span>
                            </button>
                        @else
                            <a href="{{ route('login') }}" 
                               class="px-6 md:px-8 py-3 md:py-3.5 bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white rounded-xl transition-all font-medium text-base shadow-md hover:shadow-lg">
                                <i class="fas fa-sign-in-alt mr-2"></i>登录后下载
                            </a>
                        @endauth
                    </div>

                    <!-- 书籍信息 -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm md:text-base">
                        <div class="flex items-center text-gray-600 dark:text-gray-400">
                            <i class="fas fa-file-alt w-5 text-gray-400 dark:text-gray-500 mr-2"></i>
                            <span>格式：{{ $book->file_format }}</span>
                        </div>
                        <div class="flex items-center text-gray-600 dark:text-gray-400">
                            <i class="fas fa-weight w-5 text-gray-400 dark:text-gray-500 mr-2"></i>
                            <span>大小：{{ $book->file_size }}</span>
                        </div>
                        @if($book->publisher)
                            <div class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-building w-5 text-gray-400 dark:text-gray-500 mr-2"></i>
                                <span>{{ $book->publisher }}</span>
                            </div>
                        @endif
                        @if($book->published_at)
                            <div class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-calendar w-5 text-gray-400 dark:text-gray-500 mr-2"></i>
                                <span>{{ $book->published_at }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 简介卡片 -->
        @if($book->description)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-4 md:p-6 lg:p-8 mb-4 md:mb-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-lg md:text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">内容简介</h2>
                <p class="text-gray-700 dark:text-gray-300 leading-relaxed text-sm md:text-base lg:text-lg">{{ $book->description }}</p>
            </div>
        @endif

        <!-- 相关推荐 -->
        @if(!empty($related_books))
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
                <h2 class="text-lg md:text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4 md:mb-6">相关推荐</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2 md:gap-4 lg:gap-6">
                    @foreach($related_books as $relatedBook)
                        <a href="{{ route('book.detail', $relatedBook['id']) }}" class="group">
                            <div class="book-card bg-gray-50 dark:bg-gray-700/50 rounded-lg overflow-hidden hover:shadow-md border border-gray-200 dark:border-gray-600">
                                <div class="aspect-[3/4] bg-gray-100 dark:bg-gray-700 relative overflow-hidden">
                                    @if(!empty($relatedBook['cover_url']))
                                        <img src="{{ \App\Helpers\ImageHelper::getThumbnailUrl($relatedBook['cover_url']) }}" 
                                             alt="{{ $relatedBook['title'] }}" 
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                             loading="lazy">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                            <i class="fas fa-book text-2xl"></i>
                                        </div>
                                    @endif
                                </div>
                                <div class="p-2">
                                    <h3 class="font-medium text-xs mb-1 line-clamp-2 text-gray-800 dark:text-gray-200 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">
                                        {{ $relatedBook['title'] }}
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1">{{ $relatedBook['author'] ?? '未知作者' }}</p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="text-center py-16">
            <i class="fas fa-exclamation-circle text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400">书籍不存在</p>
        </div>
    @endif
</div>

@auth
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 点赞功能
    document.querySelectorAll('.btn-like').forEach(btn => {
        btn.addEventListener('click', function() {
            const bookId = this.dataset.bookId;
            const isLiked = this.dataset.liked === '1';
            const url = isLiked ? '{{ route("book.unlike") }}' : '{{ route("book.like") }}';
            const likeCount = this.querySelector('.like-count');
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ book_id: bookId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.dataset.liked = isLiked ? '0' : '1';
                    likeCount.textContent = data.like_count;
                    if (isLiked) {
                        this.classList.remove('bg-red-100', 'dark:bg-red-900/30', 'text-red-600', 'dark:text-red-400');
                        this.classList.add('bg-gray-100', 'dark:bg-gray-700', 'text-gray-600', 'dark:text-gray-300');
                    } else {
                        this.classList.remove('bg-gray-100', 'dark:bg-gray-700', 'text-gray-600', 'dark:text-gray-300');
                        this.classList.add('bg-red-100', 'dark:bg-red-900/30', 'text-red-600', 'dark:text-red-400');
                    }
                } else {
                    alert(data.message);
                }
            });
        });
    });

    // 收藏功能
    document.querySelectorAll('.btn-favorite').forEach(btn => {
        btn.addEventListener('click', function() {
            const bookId = this.dataset.bookId;
            const isFavorited = this.dataset.favorited === '1';
            const url = isFavorited ? '{{ route("book.unfavorite") }}' : '{{ route("book.favorite") }}';
            const favoriteCount = this.querySelector('.favorite-count');
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ book_id: bookId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.dataset.favorited = isFavorited ? '0' : '1';
                    favoriteCount.textContent = data.favorite_count;
                    if (isFavorited) {
                        this.classList.remove('bg-yellow-100', 'dark:bg-yellow-900/30', 'text-yellow-600', 'dark:text-yellow-400');
                        this.classList.add('bg-gray-100', 'dark:bg-gray-700', 'text-gray-600', 'dark:text-gray-300');
                    } else {
                        this.classList.remove('bg-gray-100', 'dark:bg-gray-700', 'text-gray-600', 'dark:text-gray-300');
                        this.classList.add('bg-yellow-100', 'dark:bg-yellow-900/30', 'text-yellow-600', 'dark:text-yellow-400');
                    }
                } else {
                    alert(data.message);
                }
            });
        });
    });
});
</script>
@endpush
@endauth
@endsection

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
    <!-- 错误信息提示 -->
    @if(session('error'))
    <div class="max-w-4xl mx-auto mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg text-sm">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif

    <!-- 书籍信息卡片 -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md overflow-hidden mb-4 md:mb-6 border border-gray-200 dark:border-gray-700">
        <div class="flex flex-col md:flex-row">
            <!-- 封面 -->
            <div class="flex-shrink-0 w-full md:w-56 lg:w-64 p-4 md:p-6 lg:p-8 flex justify-center md:justify-start">
                @if($book->cover_url)
                <img src="{{ Storage::url( ltrim($book->cover_url, '/') ) }}"
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
                    <i class="fas fa-user text-gray-400 dark:text-gray-500 mr-2"></i>{{ $book->author ?: '未知作者' }}
                </p>

                @php
                    $showDownloadButton = auth()->check() || ($can_guest_download ?? false);
                @endphp

                <!-- 操作按钮 -->
                <div class="flex flex-wrap gap-3 mb-6">
                    @if($showDownloadButton)
                    <button id="btn-download"
                        data-download-url="{{ route('book.download', $book->id) }}"
                        class="btn-download px-6 md:px-8 py-3 md:py-3.5 bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white rounded-xl transition-all font-medium text-base shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-download mr-2"></i> <span class="download-text">下载</span>
                    </button>
                    @endif

                    @auth
                    <button class="btn-like px-5 md:px-6 py-3 md:py-3.5 rounded-xl font-medium active:scale-95 transition-all {{ $book->is_liked ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }} hover:shadow-md"
                        data-book-id="{{ $book->id }}"
                        data-liked="{{ $book->is_liked ? '1' : '0' }}">
                        <i class="fas fa-heart mr-2"></i> 点赞
                        <span class="like-count">{{ $book->like_count }}</span>
                    </button>
                    <button class="btn-favorite px-5 md:px-6 py-3 md:py-3.5 rounded-xl font-medium active:scale-95 transition-all {{ $book->is_favorited ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }} hover:shadow-md"
                        data-book-id="{{ $book->id }}"
                        data-favorited="{{ $book->is_favorited ? '1' : '0' }}">
                        <i class="fas fa-star mr-2"></i> 收藏
                        <span class="favorite-count">{{ $book->favorite_count }}</span>
                    </button>
                    @elseif(!$showDownloadButton)
                    <a href="{{ route('login', ['intended' => route('book.detail', $book->id)]) }}"
                        class="px-6 md:px-8 py-3 md:py-3.5 bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white rounded-xl transition-all font-medium text-base shadow-md hover:shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>登录后下载
                    </a>
                    @endauth
                </div>


                <!-- 书籍信息 -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm md:text-base mb-6">
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                        <i class="fas fa-file-alt text-gray-400 dark:text-gray-500 mr-2"></i>
                        <span>格式：{{ $book->file_format }}</span>
                    </div>
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                        <i class="fas fa-weight text-gray-400 dark:text-gray-500 mr-2"></i>
                        <span>大小：{{ \App\Helpers\FileHelper::formatFileSize($book->file_size) }}</span>
                    </div>
                    @if($book->publisher)
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                        <i class="fas fa-building text-gray-400 dark:text-gray-500 mr-2"></i>
                        <span>{{ $book->publisher }}</span>
                    </div>
                    @endif
                    @if($book->published_at)
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                        <i class="fas fa-calendar text-gray-400 dark:text-gray-500 mr-2"></i>
                        <span title="出版日期">出版于{{ $book->published_at->format('Y年m月') }}</span>
                    </div>
                    @endif
                </div>

                <!-- 标签 -->
                @if($book->tags && $book->tags->count() > 0)
                <div class="mb-6">
                    <div class="flex flex-wrap gap-2">
                        @foreach($book->tags as $tag)
                        <a href="{{ route('tag.books', ['tag' => urlencode($tag->tag_name)]) }}"
                            class="inline-flex items-center px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-sm font-medium hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
                            <i class="fas fa-tag mr-1.5 text-xs"></i>{{ $tag->tag_name }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- 下载次数  -->
                @if(isset($remaining_downloads))
                <div class="max-w-4xl mx-auto  bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg text-sm">
                    <i class="fas fa-info-circle mr-1"></i>
                    今日剩余下载次数：<span class="font-semibold {{ $remaining_downloads > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $remaining_downloads }}</span> 次
                </div>
                @endif
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
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2 md:gap-4 lg:gap-6">
            @foreach($related_books as $relatedBook)
            @include('partials.book_card', ['book' => $relatedBook])
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 下载功能 - 简单实现，防止重复点击
        const downloadBtn = document.getElementById('btn-download');
        if (downloadBtn) {
            let isDownloading = false;

            downloadBtn.addEventListener('click', function(e) {
                e.preventDefault();

                if (isDownloading) {
                    return;
                }

                isDownloading = true;
                const downloadUrl = this.dataset.downloadUrl;
                const downloadText = this.querySelector('.download-text');
                const downloadIcon = this.querySelector('i');

                // 禁用按钮并显示加载状态
                this.disabled = true;
                downloadText.textContent = '下载中...';
                downloadIcon.classList.remove('fa-download');
                downloadIcon.classList.add('fa-spinner', 'fa-spin');

                // 直接跳转到下载链接
                // 如果下载失败，服务器会重定向回详情页，错误信息会自动显示在页面顶部
                window.location.href = downloadUrl;

                // 10秒后恢复按钮状态（防止页面卡住）
                setTimeout(() => {
                    isDownloading = false;
                    this.disabled = false;
                    downloadText.textContent = '下载';
                    downloadIcon.classList.remove('fa-spinner', 'fa-spin');
                    downloadIcon.classList.add('fa-download');
                }, 10000);
            });
        }

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
                        body: JSON.stringify({
                            book_id: bookId
                        })
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
                        body: JSON.stringify({
                            book_id: bookId
                        })
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
@endsection
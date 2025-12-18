<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="format-detection" content="telephone=no">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <?php
    $current_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $canonical_url = $current_url;
    // 移除page参数来生成canonical URL（如果page=1）
    if (isset($_GET['page']) && $_GET['page'] == 1) {
        $params = $_GET;
        unset($params['page']);
        $canonical_url = 'https://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
        if (!empty($params)) {
            $canonical_url .= '?' . http_build_query($params);
        }
    }

    // 生成SEO meta标签
    $pageTitle = $pageTitle ?? ($query ?? false ? htmlspecialchars($query) . ' - 电子书搜索结果 - 七秒电子书' : '七秒电子书 - 地表最强电子书搜索引擎');
    $pageDescription = $pageDescription ?? ($query ?? false ? '搜索"' . htmlspecialchars($query) . '"的电子书结果，提供PDF、EPUB、MOBI等格式免费下载' : '七秒电子书提供海量免费电子书资源，支持PDF、EPUB、MOBI等格式一键搜索与高速下载，涵盖小说、教材、技术书籍等，打造便捷阅读体验。');
    $pageKeywords = $pageKeywords ?? ('电子书下载, 免费电子书, 电子书搜索, PDF电子书, EPUB下载, 七秒电子书, 小说电子书, 电子书资源站' . ($query ?? false ? ',' . htmlspecialchars($query) : ''));
    ?>

    <title><?php echo $pageTitle; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">

    <?php if (isset($book) && $action === 'detail'): ?>
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="book">
        <meta property="og:url" content="<?php echo htmlspecialchars($current_url); ?>">
        <meta property="og:title" content="<?php echo htmlspecialchars($book['title']); ?> - <?php echo htmlspecialchars($book['author']); ?>">
        <meta property="og:description" content="<?php echo htmlspecialchars(mb_substr(strip_tags($book['description'] ?: '暂无简介'), 0, 200)); ?>">
        <meta property="og:image" content="<?php echo "https://" . $_SERVER['HTTP_HOST'] . $book['cover_url']; ?>">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:site_name" content="七秒电子书">
        <meta property="og:locale" content="zh_CN">

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:url" content="<?php echo htmlspecialchars($current_url); ?>">
        <meta name="twitter:title" content="<?php echo htmlspecialchars($book['title']); ?> - <?php echo htmlspecialchars($book['author']); ?>">
        <meta name="twitter:description" content="<?php echo htmlspecialchars(mb_substr(strip_tags($book['description'] ?: '暂无简介'), 0, 200)); ?>">
        <meta name="twitter:image" content="<?php echo "https://" . $_SERVER['HTTP_HOST'] . $book['cover_url']; ?>">

        <!-- Book Schema.org Structured Data -->
        <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "Book",
                "name": <?php echo json_encode($book['title'], JSON_UNESCAPED_UNICODE); ?>,
                "author": {
                    "@type": "Person",
                    "name": <?php echo json_encode($book['author'], JSON_UNESCAPED_UNICODE); ?>
                },
                "description": <?php echo json_encode(mb_substr(strip_tags($book['description'] ?: '暂无简介'), 0, 500), JSON_UNESCAPED_UNICODE); ?>,
                "image": "https://<?php echo $_SERVER['HTTP_HOST'] . $book['cover_url']; ?>",
                "bookFormat": "<?php echo htmlspecialchars($book['file_format']); ?>",
                "inLanguage": "zh-CN",
                "publisher": {
                    "@type": "Organization",
                    "name": <?php echo json_encode($book['publisher'] ?? '七秒电子书', JSON_UNESCAPED_UNICODE); ?>
                },
                "datePublished": <?php echo json_encode($book['published_at'] ?? date('Y-m-d'), JSON_UNESCAPED_UNICODE); ?>,
                "offers": {
                    "@type": "Offer",
                    "price": "0",
                    "priceCurrency": "CNY",
                    "availability": "https://schema.org/InStock"
                }
            }
        </script>

        <!-- Breadcrumb Schema.org -->
        <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "BreadcrumbList",
                "itemListElement": [{
                    "@type": "ListItem",
                    "position": 1,
                    "name": "首页",
                    "item": "https://<?php echo $_SERVER['HTTP_HOST']; ?>/"
                }, {
                    "@type": "ListItem",
                    "position": 2,
                    "name": <?php echo json_encode($book['title'], JSON_UNESCAPED_UNICODE); ?>,
                    "item": "<?php echo htmlspecialchars($current_url); ?>"
                }]
            }
        </script>
    <?php else: ?>
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?php echo htmlspecialchars($current_url); ?>">
        <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
        <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
        <meta property="og:site_name" content="七秒电子书">
        <meta property="og:locale" content="zh_CN">

        <!-- Website Schema.org Structured Data -->
        <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "WebSite",
                "name": "七秒电子书",
                "url": "https://<?php echo $_SERVER['HTTP_HOST']; ?>/",
                "potentialAction": {
                    "@type": "SearchAction",
                    "target": "https://<?php echo $_SERVER['HTTP_HOST']; ?>/?q={search_term_string}",
                    "query-input": "required name=search_term_string"
                }
            }
        </script>

        <?php if (isset($query) && $query && isset($books) && !empty($books)): ?>
            <!-- SearchResultsPage Schema.org -->
            <script type="application/ld+json">
                {
                    "@context": "https://schema.org",
                    "@type": "SearchResultsPage",
                    "url": "<?php echo htmlspecialchars($current_url); ?>",
                    "mainEntity": {
                        "@type": "ItemList",
                        "numberOfItems": <?php echo $total ?? 0; ?>,
                        "itemListElement": [
                            <?php foreach (array_slice($books, 0, 10) as $idx => $book_item): ?> {
                                    "@type": "ListItem",
                                    "position": <?php echo $idx + 1; ?>,
                                    "item": {
                                        "@type": "Book",
                                        "name": <?php echo json_encode($book_item['title'], JSON_UNESCAPED_UNICODE); ?>,
                                        "author": <?php echo json_encode($book_item['author'], JSON_UNESCAPED_UNICODE); ?>,
                                        "url": "https://<?php echo $_SERVER['HTTP_HOST']; ?>/?action=detail&id=<?php echo $book_item['id']; ?>"
                                    }
                                }
                                <?php echo $idx < min(9, count($books) - 1) ? ',' : ''; ?>
                            <?php endforeach; ?>
                        ]
                    }
                }
            </script>
        <?php endif; ?>
    <?php endif; ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* 移动端优化 */
        @media (max-width: 640px) {

            button,
            a[role="button"],
            input[type="submit"],
            input[type="button"] {
                min-height: 44px;
                min-width: 44px;
            }

            body {
                -webkit-text-size-adjust: 100%;
                -moz-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
                text-size-adjust: 100%;
            }
        }

        img {
            max-width: 100%;
            height: auto;
        }

        html {
            scroll-behavior: smooth;
        }

        *:focus-visible {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
    </style>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-S1VQ1GTE0R"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', 'G-S1VQ1GTE0R');
    </script>
    <script type="text/javascript">
        (function(c, l, a, r, i, t, y) {
            c[a] = c[a] || function() {
                (c[a].q = c[a].q || []).push(arguments)
            };
            t = l.createElement(r);
            t.async = 1;
            t.src = "https://www.clarity.ms/tag/" + i;
            y = l.getElementsByTagName(r)[0];
            y.parentNode.insertBefore(t, y);
        })(window, document, "clarity", "script", "u6azio9pp0");
    </script>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php

    use App\Auth\Auth;
    use App\Service\DownloadService;

    $auth = $auth ?? new Auth();
    ?>

    <!-- 导航栏 -->
    <nav class="bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg sticky top-0 z-50" role="navigation" aria-label="主导航">
        <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8">
            <div class="flex justify-between items-center h-14 sm:h-16">
                <div class="flex items-center flex-shrink-0">
                    <a href="./" class="flex items-center focus:outline-none focus:ring-2 focus:ring-white rounded" aria-label="返回首页">
                        <i class="fas fa-book text-xl sm:text-2xl mr-2" aria-hidden="true"></i>
                        <span class="text-lg sm:text-xl font-bold">七秒电子书</span>
                    </a>
                </div>
                <div class="flex items-center gap-1 sm:gap-3">
                    <a href="?action=popular_searches" class="flex items-center bg-white/20 hover:bg-white/30 px-1.5 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition focus:outline-none focus:ring-2 focus:ring-white whitespace-nowrap" aria-label="热门搜索">
                        <i class="fas fa-fire sm:mr-1" aria-hidden="true"></i><span class="hidden sm:inline">热门搜索</span>
                    </a>
                    <a href="?action=download_ranking" class="flex items-center bg-white/20 hover:bg-white/30 px-1.5 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition focus:outline-none focus:ring-2 focus:ring-white whitespace-nowrap" aria-label="下载排行">
                        <i class="fas fa-trophy sm:mr-1" aria-hidden="true"></i><span class="hidden sm:inline">下载排行</span>
                    </a>
                    <a href="?action=latest" class="flex items-center bg-white/20 hover:bg-white/30 px-1.5 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition focus:outline-none focus:ring-2 focus:ring-white whitespace-nowrap" aria-label="最近更新">
                        <i class="fas fa-clock sm:mr-1" aria-hidden="true"></i><span class="hidden sm:inline">最近更新</span>
                    </a>

                    <?php if ($auth->isLoggedIn()): ?>
                        <div class="hidden sm:flex items-center gap-2 text-xs sm:text-sm">
                            <a href="?action=profile" class="hover:underline focus:outline-none focus:ring-2 focus:ring-white rounded px-1" aria-label="个人资料">
                                <span>欢迎, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></strong></span>
                            </a>
                            <?php if ($auth->getUserRole() === 'user'): ?>
                                <?php $downloadService = new DownloadService(); ?>
                                <span class="bg-green-500 px-2 py-1 rounded text-xs whitespace-nowrap">已下载: <?php echo $downloadService->getUserDownloadCount($auth->getUserId()); ?>/5</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($auth->isAdmin()): ?>
                            <a href="?action=dashboard" class="bg-purple-500 hover:bg-purple-600 px-2 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition focus:outline-none focus:ring-2 focus:ring-white whitespace-nowrap" aria-label="管理后台">
                                <i class="fas fa-cog mr-1" aria-hidden="true"></i><span class="hidden sm:inline">管理后台</span>
                            </a>
                        <?php endif; ?>
                        <a href="?logout=1" class="bg-red-500 hover:bg-red-600 px-2 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition focus:outline-none focus:ring-2 focus:ring-white whitespace-nowrap" aria-label="退出登录">
                            <i class="fas fa-sign-out-alt sm:mr-1" aria-hidden="true"></i><span class="hidden sm:inline">退出</span>
                        </a>
                    <?php else: ?>
                        <span class="hidden sm:inline text-xs sm:text-sm">游客</span>
                        <a href="?action=login" class="bg-green-500 hover:bg-green-600 px-3 sm:px-4 py-1.5 sm:py-2 rounded text-xs sm:text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-white whitespace-nowrap" aria-label="用户登录">
                            <i class="fas fa-user sm:mr-1" aria-hidden="true"></i><span>登录</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <?php
    $error = $this->get('error');
    if (!empty($error)): ?>
        <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 pt-4 sm:pt-6 lg:pt-8">
            <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            </div>
        </div>

    <?php endif; ?>


    <?php if (isset($popular_searches) && !empty($popular_searches) && ($action ?? '') === 'search'): ?>
        <!-- 热门搜索 -->
        <div class="bg-white border-b border-gray-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8 py-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs sm:text-sm text-gray-600 font-medium whitespace-nowrap">
                        <i class="fas fa-fire text-orange-500 mr-1" aria-hidden="true"></i>热门搜索：
                    </span>
                    <?php foreach ($popular_searches as $popular): ?>
                        <a href="?q=<?php echo urlencode($popular['search_query']); ?>"
                            class="inline-flex items-center px-2 sm:px-3 py-1 text-xs sm:text-sm bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 rounded-full transition-colors whitespace-nowrap">
                            <?php echo htmlspecialchars($popular['search_query']); ?>
                            <span class="ml-1 text-gray-500 text-xs">(<?php echo $popular['search_count']; ?>)</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php echo $content ?? ''; ?>

    <script>
        // Cookie 操作函数
        function setCookie(name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/';
        }

        function getCookie(name) {
            const nameEQ = name + '=';
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }

        // 布局切换函数
        function switchBookLayout(layout, containerId) {
            setCookie('book_layout', layout, 365);
            location.reload();
        }

        // 点赞/取消点赞
        function toggleLike(bookId) {
            const likeBtn = document.getElementById('likeBtn');
            const likeCountSpan = document.getElementById('likeCount');
            const isLiked = likeBtn.classList.contains('bg-red-600');

            likeBtn.disabled = true;

            const action = isLiked ? 'unlike' : 'like';
            const formData = new FormData();
            formData.append('book_id', bookId);

            fetch(`?action=${action}`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (action === 'like') {
                            likeBtn.classList.remove('bg-gray-200', 'hover:bg-gray-300', 'text-gray-700', 'focus:ring-gray-500');
                            likeBtn.classList.add('bg-red-600', 'hover:bg-red-700', 'text-white', 'focus:ring-red-500');
                        } else {
                            likeBtn.classList.remove('bg-red-600', 'hover:bg-red-700', 'text-white', 'focus:ring-red-500');
                            likeBtn.classList.add('bg-gray-200', 'hover:bg-gray-300', 'text-gray-700', 'focus:ring-gray-500');
                        }
                        if (likeCountSpan) {
                            likeCountSpan.textContent = data.like_count || 0;
                        }
                    } else {
                        alert(data.message || '操作失败，请稍后重试');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('操作失败，请稍后重试');
                })
                .finally(() => {
                    likeBtn.disabled = false;
                });
        }

        // 收藏/取消收藏
        function toggleFavorite(bookId) {
            const favoriteBtn = document.getElementById('favoriteBtn');
            const favoriteCountSpan = document.getElementById('favoriteCount');
            const isFavorited = favoriteBtn.classList.contains('bg-yellow-600');

            favoriteBtn.disabled = true;

            const action = isFavorited ? 'unfavorite' : 'favorite';
            const formData = new FormData();
            formData.append('book_id', bookId);

            fetch(`?action=${action}`, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (action === 'favorite') {
                            favoriteBtn.classList.remove('bg-gray-200', 'hover:bg-gray-300', 'text-gray-700', 'focus:ring-gray-500');
                            favoriteBtn.classList.add('bg-yellow-600', 'hover:bg-yellow-700', 'text-white', 'focus:ring-yellow-500');
                        } else {
                            favoriteBtn.classList.remove('bg-yellow-600', 'hover:bg-yellow-700', 'text-white', 'focus:ring-yellow-500');
                            favoriteBtn.classList.add('bg-gray-200', 'hover:bg-gray-300', 'text-gray-700', 'focus:ring-gray-500');
                        }
                        if (favoriteCountSpan) {
                            favoriteCountSpan.textContent = data.favorite_count || 0;
                        }
                    } else {
                        alert(data.message || '操作失败，请稍后重试');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('操作失败，请稍后重试');
                })
                .finally(() => {
                    favoriteBtn.disabled = false;
                });
        }

        // 分享书籍
        function shareBook(bookId, bookTitle) {
            const url = window.location.origin + window.location.pathname + `?action=detail&id=${bookId}`;
            const text = `推荐一本好书：《${bookTitle}》`;

            if (navigator.share) {
                navigator.share({
                        title: bookTitle,
                        text: text,
                        url: url
                    })
                    .catch(error => {
                        if (error.name !== 'AbortError') {
                            copyToClipboard(url);
                        }
                    });
            } else {
                copyToClipboard(url);
            }
        }

        // 复制到剪贴板
        function copyToClipboard(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    alert('链接已复制到剪贴板！');
                }).catch(() => {
                    fallbackCopyToClipboard(text);
                });
            } else {
                fallbackCopyToClipboard(text);
            }
        }

        // 备用复制方法
        function fallbackCopyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    alert('链接已复制到剪贴板！');
                } else {
                    prompt('请复制以下链接：', text);
                }
            } catch (err) {
                prompt('请复制以下链接：', text);
            }

            document.body.removeChild(textArea);
        }
    </script>
</body>

</html>
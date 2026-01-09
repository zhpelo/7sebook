<!DOCTYPE html>
<html lang="zh-CN" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="format-detection" content="telephone=no">
    <meta name="theme-color" content="#dc2626">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    @php
    $currentUrl = url()->current();
    $canonicalUrl = $currentUrl;
    if (request()->get('page') == 1) {
    $params = request()->all();
    unset($params['page']);
    $canonicalUrl = url()->current() . (!empty($params) ? '?' . http_build_query($params) : '');
    }

    $pageTitle = $pageTitle ?? (isset($query) && $query ? htmlspecialchars($query) . ' - 电子书搜索结果 - 七秒电子书' : '七秒电子书 - 地表最强电子书搜索引擎');
    $pageDescription = $pageDescription ?? (isset($query) && $query ? '搜索"' . htmlspecialchars($query) . '"的电子书结果，提供PDF、EPUB、MOBI等格式免费下载' : '七秒电子书提供海量免费电子书资源，支持PDF、EPUB、MOBI等格式一键搜索与高速下载，涵盖小说、教材、技术书籍等，打造便捷阅读体验。');
    $pageKeywords = $pageKeywords ?? ('电子书下载, 免费电子书, 电子书搜索, PDF电子书, EPUB下载, 七秒电子书, 小说电子书, 电子书资源站' . (isset($query) && $query ? ',' . htmlspecialchars($query) : ''));
    @endphp

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ htmlspecialchars($pageDescription) }}">
    <meta name="keywords" content="{{ htmlspecialchars($pageKeywords) }}">
    <link rel="canonical" href="{{ htmlspecialchars($canonicalUrl) }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @stack('meta')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @stack('styles')
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

<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased">
    <!-- 顶部导航栏（桌面端） -->
    <nav class="hidden md:flex fixed top-0 left-0 right-0 bg-white dark:bg-gray-800 shadow-md z-40 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto w-full px-4 lg:px-6">
            <div class="flex items-center justify-between h-16">
                <a href="{{ route('home') }}" class="text-2xl font-bold text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors">
                    <i class="fas fa-book mr-2"></i>七秒电子书
                </a>
                <div class="flex items-center space-x-1">
                    <a href="{{ route('home') }}" class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('home') || request()->routeIs('search') ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        首页
                    </a>
                    <a href="{{ route('latest') }}" class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('latest') ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        最新书籍
                    </a>
                    <a href="{{ route('download_ranking') }}" class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('download_ranking') ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        下载排行
                    </a>
                    <a href="{{ route('popular_searches') }}" class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ request()->routeIs('popular_searches') ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        今日热搜
                    </a>
                    @auth
                    <div class="ml-2 pl-2 border-l border-gray-300 dark:border-gray-600 flex items-center">
                        <!-- 用户下拉菜单 -->
                        <div class="relative" id="userMenuContainer">
                            <button id="userMenuToggle" class="px-4 py-2 text-sm font-medium rounded-lg transition-all flex items-center space-x-1 {{ request()->routeIs('profile') || request()->routeIs('my_likes') || request()->routeIs('my_favorites') ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                                <i class="fas fa-user mr-1"></i>
                                <span>{{ auth()->user()->username }}</span>
                                <i class="fas fa-chevron-down ml-1 text-xs transition-transform" id="userMenuIcon"></i>
                            </button>

                            <!-- 下拉菜单 -->
                            <div id="userMenuDropdown" class="hidden absolute right-0 mt-2 w-30 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50">
                                <a href="{{ route('profile') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ request()->routeIs('profile') ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400' : '' }}">
                                    <i class="fas fa-user-circle mr-2"></i>个人资料
                                </a>
                                <a href="{{ route('my_favorites') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ request()->routeIs('my_favorites') ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400' : '' }}">
                                    <i class="fas fa-star mr-2"></i>我的收藏
                                </a>
                                <a href="{{ route('my_likes') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ request()->routeIs('my_likes') ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400' : '' }}">
                                    <i class="fas fa-heart mr-2"></i>我的点赞
                                </a>
                                @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-cog mr-2"></i>管理后台
                                </a>
                                @endif
                                <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                                <a href="{{ route('logout') }}" class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                    <i class="fas fa-sign-out-alt mr-2"></i>退出登录
                                </a>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="ml-2 pl-2 border-l border-gray-300 dark:border-gray-600 flex items-center space-x-1">
                        <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all">
                            登录
                        </a>
                        <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-medium bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white rounded-lg transition-all shadow-md">
                            注册
                        </a>
                    </div>
                    @endauth
                    <!-- 暗色模式切换按钮 -->
                    <button id="darkModeToggle" type="button" onclick="window.toggleDarkMode()" class="ml-2 p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all" aria-label="切换暗色模式">
                        <i id="darkModeIcon" class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- 移动端顶部栏 -->
    <div class="md:hidden fixed top-0 left-0 right-0 bg-white dark:bg-gray-800 shadow-md z-40 border-b border-gray-200 dark:border-gray-700">
        <div class="px-4 py-3 flex items-center justify-between">
            <button id="mobileMenuToggle" class="p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                <i class="fas fa-bars"></i>
            </button>

            <a href="{{ route('home') }}" class="text-xl font-bold text-red-600 dark:text-red-400">
                <i class="fas fa-book mr-1"></i>七秒电子书
            </a>
            <button id="darkModeToggleMobile" type="button" onclick="window.toggleDarkMode()" class="p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="切换暗色模式">
                <i id="darkModeIconMobile" class="fas fa-moon"></i>
            </button>
        </div>
        <!-- 移动端菜单 -->
        <div id="mobileMenu" class="hidden px-4 pb-3 border-t border-gray-200 dark:border-gray-700">
            <div class="pt-3 space-y-2">
                <a href="{{ route('home') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('home') || request()->routeIs('search') ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-home mr-2"></i>首页
                </a>
                <a href="{{ route('latest') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('latest') ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-clock mr-2"></i>最新书籍
                </a>
                <a href="{{ route('download_ranking') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('download_ranking') ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-trophy mr-2"></i>下载排行
                </a>
                <a href="{{ route('popular_searches') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('popular_searches') ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-fire mr-2"></i>今日热搜
                </a>
                @auth

                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-cog mr-2"></i>管理后台
                    </a>
                    @endif

                <div class="border-t border-gray-200 dark:border-gray-700 my-2"></div>
                <a href="{{ route('logout') }}" class="block px-3 py-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="fas fa-sign-out-alt mr-2"></i>退出登录
                </a>
                @endauth
            </div>
        </div>
        <!-- 移动端搜索栏 -->
        <div class="px-4 pb-3">
            <form method="GET" action="{{ route('search') }}" class="flex gap-2">
                <input type="text" name="q" value="{{ $query ?? '' }}"
                    placeholder="搜索电子书..."
                    class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:bg-white dark:focus:bg-gray-600 text-gray-900 dark:text-gray-100">
                <button type="submit" class="px-4 py-2.5 bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white rounded-full transition-all shadow-md">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- 主要内容 -->
    <main class="pt-30 md:pt-20 pb-30 md:pb-6 min-h-screen">
        <div class="max-w-7xl mx-auto px-2 md:px-4 lg:px-6">
            @if(session('message'))
            <div class="fixed top-32 md:top-16 left-1/2 transform -translate-x-1/2 z-50 bg-green-500 dark:bg-green-600 text-white px-4 py-3 rounded-lg shadow-lg text-sm animate-slide-down max-w-md">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span>{{ session('message') }}</span>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            @endif
            @if(session('error'))
            <div class="fixed top-32 md:top-16 left-1/2 transform -translate-x-1/2 z-50 bg-red-500 dark:bg-red-600 text-white px-4 py-3 rounded-lg shadow-lg text-sm animate-slide-down max-w-md">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- 移动端底部导航栏 -->
    <nav class="bottom-nav md:hidden">
        <div class="flex justify-around items-center h-16">
            <a href="{{ route('home') }}" class="nav-item flex flex-col items-center justify-center flex-1 {{ request()->routeIs('home') || request()->routeIs('search') ? 'active text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                <i class="fas fa-home nav-icon text-xl mb-1"></i>
                <span class="text-xs">首页</span>
            </a>
            <a href="{{ route('latest') }}" class="nav-item flex flex-col items-center justify-center flex-1 {{ request()->routeIs('latest') ? 'active text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                <i class="fas fa-clock nav-icon text-xl mb-1"></i>
                <span class="text-xs">最新</span>
            </a>
            <a href="{{ route('download_ranking') }}" class="nav-item flex flex-col items-center justify-center flex-1 {{ request()->routeIs('download_ranking') ? 'active text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                <i class="fas fa-trophy nav-icon text-xl mb-1"></i>
                <span class="text-xs">排行</span>
            </a>
            @auth
            <a href="{{ route('profile') }}" class="nav-item flex flex-col items-center justify-center flex-1 {{ request()->routeIs('profile') || request()->routeIs('my_likes') || request()->routeIs('my_favorites') ? 'active text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                <i class="fas fa-user nav-icon text-xl mb-1"></i>
                <span class="text-xs">我的</span>
            </a>
            @else
            <a href="{{ route('login') }}" class="nav-item flex flex-col items-center justify-center flex-1 {{ request()->routeIs('login') || request()->routeIs('register') ? 'active text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">
                <i class="fas fa-sign-in-alt nav-icon text-xl mb-1"></i>
                <span class="text-xs">登录</span>
            </a>
            @endauth
        </div>
    </nav>

    @stack('scripts')

    <script>
        // 更新图标显示
        function updateDarkModeIcons() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            const darkModeIcon = document.getElementById('darkModeIcon');
            const darkModeIconMobile = document.getElementById('darkModeIconMobile');

            if (darkModeIcon) {
                darkModeIcon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            }
            if (darkModeIconMobile) {
                darkModeIconMobile.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            }
        }

        // 全局暗色模式切换函数
        window.toggleDarkMode = function() {
            const html = document.documentElement;
            const body = document.body;

            // 切换 dark 类
            html.classList.toggle('dark');
            body.classList.toggle('dark');

            const isDark = html.classList.contains('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');

            // 更新图标
            updateDarkModeIcons();

            console.log('暗色模式已切换:', isDark ? 'dark' : 'light');
        };

        // 初始化暗色模式
        (function() {
            'use strict';

            const html = document.documentElement;
            const body = document.body;

            // 检查本地存储或系统偏好
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                html.classList.add('dark');
                body.classList.add('dark');
            } else {
                html.classList.remove('dark');
                body.classList.remove('dark');
            }

            // 初始化图标显示
            updateDarkModeIcons();

            console.log('暗色模式已初始化:', html.classList.contains('dark') ? 'dark' : 'light');
        })();

        // 移动端菜单切换
        function initMobileMenu() {
            const menuToggle = document.getElementById('mobileMenuToggle');
            const mobileMenu = document.getElementById('mobileMenu');

            if (menuToggle && mobileMenu) {
                menuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    mobileMenu.classList.toggle('hidden');
                });
            }
        }

        // 自动隐藏消息提示
        function initNotifications() {
            setTimeout(function() {
                const messages = document.querySelectorAll('.animate-slide-down');
                messages.forEach(msg => {
                    msg.style.transition = 'opacity 0.3s';
                    msg.style.opacity = '0';
                    setTimeout(() => msg.remove(), 300);
                });
            }, 3000);
        }

        // 用户下拉菜单
        function initUserMenu() {
            const menuToggle = document.getElementById('userMenuToggle');
            const menuDropdown = document.getElementById('userMenuDropdown');
            const menuIcon = document.getElementById('userMenuIcon');

            if (menuToggle && menuDropdown) {
                menuToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isHidden = menuDropdown.classList.contains('hidden');

                    if (isHidden) {
                        menuDropdown.classList.remove('hidden');
                        if (menuIcon) {
                            menuIcon.classList.add('rotate-180');
                        }
                    } else {
                        menuDropdown.classList.add('hidden');
                        if (menuIcon) {
                            menuIcon.classList.remove('rotate-180');
                        }
                    }
                });

                // 点击外部区域关闭菜单
                document.addEventListener('click', function(e) {
                    const container = document.getElementById('userMenuContainer');
                    if (container && !container.contains(e.target)) {
                        menuDropdown.classList.add('hidden');
                        if (menuIcon) {
                            menuIcon.classList.remove('rotate-180');
                        }
                    }
                });
            }
        }

        // 初始化
        document.addEventListener('DOMContentLoaded', function() {
            initMobileMenu();
            initNotifications();
            initUserMenu();
        });
    </script>
</body>

</html>
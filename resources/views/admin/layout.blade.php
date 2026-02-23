<!DOCTYPE html>
<html lang="zh-CN" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle ?? '七秒电子书后台管理' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased">
    <!-- 侧边栏（桌面端） -->
    <aside class="hidden lg:block fixed top-0 left-0 h-screen w-64 bg-white dark:bg-gray-800 shadow-lg z-30 overflow-y-auto border-r border-gray-200 dark:border-gray-700">
        <div class="p-4 bg-gradient-to-br from-red-600 to-red-700 dark:from-red-700 dark:to-red-800 text-white">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-bold flex items-center">
                    <i class="fas fa-book mr-2"></i>
                    <span>七秒后台</span>
                </h1>
            </div>
        </div>
        @php($currentAdmin = auth()->user())
        <nav class="p-2">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-3 rounded-lg mb-1 transition-all {{ ($action ?? 'dashboard') === 'dashboard' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i class="fas fa-tachometer-alt w-5 mr-3"></i>
                <span>仪表盘</span>
            </a>
            <a href="{{ route('admin.users') }}" class="flex items-center px-4 py-3 rounded-lg mb-1 transition-all {{ ($action ?? '') === 'users' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i class="fas fa-users w-5 mr-3"></i>
                <span>用户管理</span>
            </a>
            <a href="{{ route('admin.books') }}" class="flex items-center px-4 py-3 rounded-lg mb-1 transition-all {{ ($action ?? '') === 'books' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i class="fas fa-book w-5 mr-3"></i>
                <span>书籍管理</span>
            </a>
            <a href="{{ route('admin.downloads') }}" class="flex items-center px-4 py-3 rounded-lg mb-1 transition-all {{ ($action ?? '') === 'downloads' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i class="fas fa-download w-5 mr-3"></i>
                <span>下载记录</span>
            </a>
            <a href="{{ route('admin.search_history') }}" class="flex items-center px-4 py-3 rounded-lg mb-1 transition-all {{ ($action ?? '') === 'search_history' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i class="fas fa-search w-5 mr-3"></i>
                <span>搜索历史</span>
            </a>
            <a href="{{ route('admin.email_logs') }}" class="flex items-center px-4 py-3 rounded-lg mb-1 transition-all {{ ($action ?? '') === 'email_logs' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i class="fas fa-envelope w-5 mr-3"></i>
                <span>邮件记录</span>
            </a>
            <a href="{{ route('admin.tags') }}" class="flex items-center px-4 py-3 rounded-lg mb-1 transition-all {{ ($action ?? '') === 'tags' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i class="fas fa-tags w-5 mr-3"></i>
                <span>标签管理</span>
            </a>

            <a href="{{ route('admin.site_settings') }}" class="flex items-center px-4 py-3 rounded-lg mb-1 transition-all {{ ($action ?? '') === 'site_settings' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i class="fas fa-globe w-5 mr-3"></i>
                <span>网站设置</span>
            </a>
            <a href="{{ route('admin.download_settings') }}" class="flex items-center px-4 py-3 rounded-lg mb-1 transition-all {{ ($action ?? '') === 'download_settings' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i class="fas fa-download w-5 mr-3"></i>
                <span>下载设置</span>
            </a>
            <a href="{{ route('admin.smtp_settings') }}" class="flex items-center px-4 py-3 rounded-lg mb-1 transition-all {{ ($action ?? '') === 'smtp_settings' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i class="fas fa-paper-plane w-5 mr-3"></i>
                <span>SMTP设置</span>
            </a>
            <div class="border-t border-gray-200 dark:border-gray-700 my-2"></div>

            <a href="{{ route('admin.settings') }}" class="flex items-center px-4 py-3 rounded-lg mb-2 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/40 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                <i class="fas fa-user-shield w-5 mr-3 text-gray-500 dark:text-gray-300"></i>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $currentAdmin?->username ?? '管理员' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $currentAdmin?->email ?? '' }}</p>
                </div>
            </a>

            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('home') }}" target="_blank" class="inline-flex items-center justify-center px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all text-sm">
                    <i class="fas fa-external-link-alt mr-1.5"></i>前台
                </a>
                <a href="{{ route('logout') }}" class="inline-flex items-center justify-center px-3 py-2 rounded-lg bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white transition-all text-sm">
                    <i class="fas fa-sign-out-alt mr-1.5"></i>退出
                </a>
            </div>
        </nav>
    </aside>

    <!-- 移动端顶部栏 -->
    <nav class="lg:hidden fixed top-0 left-0 right-0 bg-white dark:bg-gray-800 shadow-md z-30 border-b border-gray-200 dark:border-gray-700">
        <div class="px-4 py-3 flex items-center justify-between">
            <button id="mobileMenuToggle" class="p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                <i class="fas fa-bars"></i>
            </button>
            <a href="{{ route('admin.dashboard') }}" class="text-lg font-bold text-red-600 dark:text-red-400">
                <i class="fas fa-book mr-1"></i>七秒后台
            </a>
            <div class="flex items-center space-x-2">
                <button id="darkModeToggleAdmin" type="button" onclick="window.toggleDarkMode()" class="p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="切换暗色模式">
                    <i id="darkModeIconAdmin" class="fas fa-moon"></i>
                </button>
                <a href="{{ route('home') }}" target="_blank" class="p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="fas fa-external-link-alt"></i>
                </a>
            </div>
        </div>
        <!-- 移动端菜单 -->
        <div id="mobileMenu" class="hidden px-4 pb-3 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <div class="pt-3 space-y-1">
                <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-lg {{ ($action ?? 'dashboard') === 'dashboard' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-tachometer-alt mr-2"></i>仪表盘
                </a>
                <a href="{{ route('admin.users') }}" class="block px-3 py-2 rounded-lg {{ ($action ?? '') === 'users' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-users mr-2"></i>用户管理
                </a>
                <a href="{{ route('admin.books') }}" class="block px-3 py-2 rounded-lg {{ ($action ?? '') === 'books' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-book mr-2"></i>书籍管理
                </a>
                <a href="{{ route('admin.downloads') }}" class="block px-3 py-2 rounded-lg {{ ($action ?? '') === 'downloads' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-download mr-2"></i>下载记录
                </a>
                <a href="{{ route('admin.search_history') }}" class="block px-3 py-2 rounded-lg {{ ($action ?? '') === 'search_history' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-search mr-2"></i>搜索历史
                </a>
                <a href="{{ route('admin.email_logs') }}" class="block px-3 py-2 rounded-lg {{ ($action ?? '') === 'email_logs' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-envelope mr-2"></i>邮件记录
                </a>
                <a href="{{ route('admin.tags') }}" class="block px-3 py-2 rounded-lg {{ ($action ?? '') === 'tags' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-tags mr-2"></i>标签管理
                </a>
   
                <a href="{{ route('admin.site_settings') }}" class="block px-3 py-2 rounded-lg {{ ($action ?? '') === 'site_settings' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-globe mr-2"></i>网站设置
                </a>
                <a href="{{ route('admin.download_settings') }}" class="block px-3 py-2 rounded-lg {{ ($action ?? '') === 'download_settings' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-download mr-2"></i>下载设置
                </a>
                <a href="{{ route('admin.smtp_settings') }}" class="block px-3 py-2 rounded-lg {{ ($action ?? '') === 'smtp_settings' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-paper-plane mr-2"></i>SMTP设置
                </a>

                <div class="border-t border-gray-200 dark:border-gray-700 my-2"></div>

                <a href="{{ route('admin.settings') }}" class="block px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/40 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-user-shield mr-2"></i>{{ $currentAdmin?->username ?? '管理员' }}
                </a>

                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('home') }}" target="_blank" class="inline-flex items-center justify-center px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm">
                        <i class="fas fa-external-link-alt mr-1.5"></i>前台
                    </a>
                    <a href="{{ route('logout') }}" class="inline-flex items-center justify-center px-3 py-2 rounded-lg bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white text-sm">
                        <i class="fas fa-sign-out-alt mr-1.5"></i>退出
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- 主内容区域 -->
    <main class="lg:ml-64 pt-14 lg:pt-0 min-h-screen">
        <div class="p-3 md:p-6 lg:p-8 max-w-7xl mx-auto">
            @if(session('message'))
                <div class="mb-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 dark:border-green-400 text-green-700 dark:text-green-400 px-4 py-3 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span>{{ session('message') }}</span>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-green-700 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 dark:border-red-400 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-red-700 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <script>
        // 更新图标显示
        function updateDarkModeIcons() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            const darkModeIcon = document.getElementById('darkModeIcon');
            const darkModeIconMobile = document.getElementById('darkModeIconMobile');
            const darkModeIconAdmin = document.getElementById('darkModeIconAdmin');
            
            if (darkModeIcon) {
                darkModeIcon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            }
            if (darkModeIconMobile) {
                darkModeIconMobile.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            }
            if (darkModeIconAdmin) {
                darkModeIconAdmin.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            }
        }
        
        // 全局暗色模式切换函数（如果前台没有定义，则定义它）
        if (typeof window.toggleDarkMode === 'undefined') {
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
        }
        
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
            if (typeof updateDarkModeIcons === 'function') {
                updateDarkModeIcons();
            } else {
                // 如果前台没有定义，在这里定义
                const darkModeIconAdmin = document.getElementById('darkModeIconAdmin');
                if (darkModeIconAdmin) {
                    const isDark = html.classList.contains('dark');
                    darkModeIconAdmin.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
                }
            }
            
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
        
        // 初始化
        document.addEventListener('DOMContentLoaded', function() {
            initMobileMenu();
        });
    </script>
    @stack('scripts')
</body>
</html>

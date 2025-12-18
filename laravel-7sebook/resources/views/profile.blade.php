@extends('layout')

@section('content')
<div class="py-4 md:py-6">
    <div class="max-w-3xl mx-auto px-2 md:px-4 lg:px-6">
        @auth
            <!-- 用户信息卡片 -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-6 mb-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-user text-red-600 dark:text-red-400 text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $user_info->username }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user_info->email }}</p>
                    </div>
                </div>

                <!-- 统计信息 -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4 text-center border border-red-100 dark:border-red-900/30">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $liked_count }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">点赞</div>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-4 text-center border border-yellow-100 dark:border-yellow-900/30">
                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $favorited_count }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">收藏</div>
                    </div>
                </div>
            </div>

            <!-- 快捷入口 -->
            <div class="grid grid-cols-2 gap-3 mb-4">
                <a href="{{ route('my_likes') }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 flex items-center justify-center hover:shadow-lg transition-all border border-gray-200 dark:border-gray-700">
                    <i class="fas fa-heart text-red-500 dark:text-red-400 text-xl mr-2"></i>
                    <span class="font-medium text-gray-800 dark:text-gray-200">我的点赞</span>
                </a>
                <a href="{{ route('my_favorites') }}" class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 flex items-center justify-center hover:shadow-lg transition-all border border-gray-200 dark:border-gray-700">
                    <i class="fas fa-star text-yellow-500 dark:text-yellow-400 text-xl mr-2"></i>
                    <span class="font-medium text-gray-800 dark:text-gray-200">我的收藏</span>
                </a>
            </div>

            <!-- 修改资料表单 -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">修改资料</h3>
                
                @if(session('message'))
                    <div class="mb-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl text-sm">
                        <i class="fas fa-check-circle mr-2"></i>{{ session('message') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl text-sm">
                        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">当前密码 *</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400 dark:text-gray-500"></i>
                            </div>
                            <input type="password" id="current_password" name="current_password" required
                                   class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">新邮箱</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400 dark:text-gray-500"></i>
                            </div>
                            <input type="email" id="email" name="email" value="{{ $user_info->email }}"
                                   class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">新密码</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400 dark:text-gray-500"></i>
                            </div>
                            <input type="password" id="new_password" name="new_password"
                                   class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">留空则不修改密码</p>
                    </div>

                    <div class="mb-6">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">确认新密码</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400 dark:text-gray-500"></i>
                            </div>
                            <input type="password" id="confirm_password" name="confirm_password"
                                   class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white py-3 rounded-xl transition-all font-medium text-base shadow-md hover:shadow-lg">
                        更新资料
                    </button>
                </form>
            </div>
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
</div>
@endsection

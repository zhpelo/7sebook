@extends('layout')

@section('content')
<div class="py-8 md:py-12">
    <div class="max-w-md mx-auto px-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 md:p-8 lg:p-10 border border-gray-200 dark:border-gray-700">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user text-red-600 dark:text-red-400 text-2xl"></i>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100">用户登录</h1>
            </div>
            
            @if(session('error'))
                <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">用户名</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400 dark:text-gray-500"></i>
                        </div>
                        <input type="text" id="username" name="username" required autofocus
                               class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">密码</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400 dark:text-gray-500"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                               class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white py-3 rounded-xl transition-all font-medium text-base shadow-md hover:shadow-lg mb-4">
                    登录
                </button>
            </form>

            <div class="text-center space-y-2">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    还没有账号？
                    <a href="{{ route('register', request()->has('intended') ? ['intended' => request()->get('intended')] : (session('intended_url') ? ['intended' => session('intended_url')] : [])) }}" class="text-red-600 dark:text-red-400 font-medium hover:text-red-700 dark:hover:text-red-300">立即注册</a>
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <a href="{{ route('forgot_password') }}" class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">忘记密码？</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('admin.layout')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">管理员设置</h2>

        @if(session('message'))
            <div class="mb-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-check-circle mr-2"></i>{{ session('message') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
        @endif

        @if($admin_user)
            <form method="POST" action="{{ route('admin.settings.post') }}">
                @csrf
                <input type="hidden" name="admin_id" value="{{ $admin_user->id }}">
                
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">用户名</label>
                    <input type="text" id="username" name="username" value="{{ $admin_user->username }}" required
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">邮箱</label>
                    <input type="email" id="email" name="email" value="{{ $admin_user->email }}" required
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">新密码</label>
                    <input type="password" id="password" name="password"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">留空则不修改密码</p>
                </div>

                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white py-3 rounded-lg transition-all font-medium shadow-md hover:shadow-lg">
                    更新管理员信息
                </button>
            </form>
        @else
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 text-yellow-700 dark:text-yellow-400 px-4 py-3 rounded-lg">
                <i class="fas fa-exclamation-triangle mr-2"></i>未找到管理员账户
            </div>
        @endif
    </div>
</div>
@endsection

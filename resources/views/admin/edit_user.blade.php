@extends('admin.layout')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('admin.users') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-all">
                <i class="fas fa-arrow-left mr-2"></i>返回
            </a>
            <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100">编辑用户</h2>
        </div>

        @if(session('error'))
            <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.edit_user.post', $edit_user->id) }}">
            @csrf
            <div class="mb-4">
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">用户名</label>
                <input type="text" id="username" name="username" value="{{ $edit_user->username }}" required
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            </div>

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">邮箱</label>
                <input type="email" id="email" name="email" value="{{ $edit_user->email }}" required
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            </div>

            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">新密码</label>
                <input type="password" id="password" name="password"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">留空则不修改密码</p>
            </div>

            <div class="mb-6">
                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">角色</label>
                <select id="role" name="role" 
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    <option value="user" {{ $edit_user->role === 'user' ? 'selected' : '' }}>普通用户</option>
                    <option value="admin" {{ $edit_user->role === 'admin' ? 'selected' : '' }}>管理员</option>
                </select>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white py-3 rounded-lg transition-all font-medium shadow-md hover:shadow-lg">
                    更新用户
                </button>
                <a href="{{ route('admin.users') }}" class="px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-all font-medium">
                    取消
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('admin.layout')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">下载设置</h2>

        <form method="POST" action="{{ route('admin.download_settings.post') }}">
            @csrf

            <div class="mb-4">
                <label for="download_permission" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">文件下载权限</label>
                <select id="download_permission" name="download_permission"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    <option value="guest" {{ old('download_permission', $settings['download_permission']) === 'guest' ? 'selected' : '' }}>允许游客下载</option>
                    <option value="user" {{ old('download_permission', $settings['download_permission']) === 'user' ? 'selected' : '' }}>允许注册用户下载</option>
                    <option value="vip" {{ old('download_permission', $settings['download_permission']) === 'vip' ? 'selected' : '' }}>仅允许VIP用户下载</option>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label for="download_limit_guest" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">游客每日次数</label>
                    <input type="number" id="download_limit_guest" name="download_limit_guest" min="0" max="9999" value="{{ old('download_limit_guest', $settings['download_limit_guest']) }}" required
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
                <div>
                    <label for="download_limit_user" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">注册用户每日次数</label>
                    <input type="number" id="download_limit_user" name="download_limit_user" min="0" max="9999" value="{{ old('download_limit_user', $settings['download_limit_user']) }}" required
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
                <div>
                    <label for="download_limit_vip" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">VIP每日次数</label>
                    <input type="number" id="download_limit_vip" name="download_limit_vip" min="0" max="9999" value="{{ old('download_limit_vip', $settings['download_limit_vip']) }}" required
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
            </div>

            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">说明：未登录访客按同一个IP统计每日下载次数。</p>

            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white py-3 rounded-lg transition-all font-medium shadow-md hover:shadow-lg">
                保存下载设置
            </button>
        </form>
    </div>
</div>
@endsection

@extends('admin.layout')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">网站设置</h2>

        <form method="POST" action="{{ route('admin.site_settings.post') }}">
            @csrf

            <div class="mb-4">
                <label for="site_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">网站名称</label>
                <input type="text" id="site_name" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            </div>

            <div class="mb-4">
                <label for="site_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">网站简介</label>
                <textarea id="site_description" name="site_description" rows="4"
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">{{ old('site_description', $settings['site_description']) }}</textarea>
            </div>

            <div class="mb-6">
                <label for="contact_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">联系邮箱</label>
                <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $settings['contact_email']) }}"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            </div>

            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white py-3 rounded-lg transition-all font-medium shadow-md hover:shadow-lg">
                保存网站设置
            </button>
        </form>
    </div>
</div>
@endsection

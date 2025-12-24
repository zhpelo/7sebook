@extends('layout')

@section('content')
<div class="py-8 md:py-12">
    <div class="max-w-md mx-auto px-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 md:p-8 lg:p-10 border border-gray-200 dark:border-gray-700 text-center">
            <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-2xl"></i>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">页面已过期</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                您的会话已过期，请刷新页面后重试。
            </p>
            <div class="space-y-3">
                <button onclick="window.location.reload()" class="w-full bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white py-3 rounded-xl transition-all font-medium text-base shadow-md hover:shadow-lg">
                    <i class="fas fa-sync-alt mr-2"></i>刷新页面
                </button>
                <a href="{{ route('home') }}" class="block w-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 py-3 rounded-xl transition-all font-medium text-base text-center">
                    <i class="fas fa-home mr-2"></i>返回首页
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

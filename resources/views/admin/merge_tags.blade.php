@extends('admin.layout')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('admin.tags') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-all">
                <i class="fas fa-arrow-left mr-2"></i>返回
            </a>
            <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100">合并标签</h2>
        </div>

        @if(session('error'))
            <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.merge_tags.post') }}">
            @csrf
            <div class="mb-4">
                <label for="source_tag" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">源标签（将被合并的标签）*</label>
                <input type="text" id="source_tag" name="source_tag" required autofocus placeholder="输入要合并的标签名称"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            </div>

            <div class="mb-6">
                <label for="target_tag" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">目标标签（合并到的标签）*</label>
                <input type="text" id="target_tag" name="target_tag" required placeholder="输入目标标签名称"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">源标签的所有书籍将被添加到目标标签，然后源标签将被删除</p>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white py-3 rounded-lg transition-all font-medium shadow-md hover:shadow-lg">
                    合并标签
                </button>
                <a href="{{ route('admin.tags') }}" class="px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-all font-medium">
                    取消
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

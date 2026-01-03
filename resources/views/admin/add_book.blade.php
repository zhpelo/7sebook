@extends('admin.layout')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-6">
            <a href="{{ route('admin.books') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-all">
                <i class="fas fa-arrow-left mr-2"></i>返回
            </a>
            <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100">添加电子书</h2>
        </div>

        @if(session('error'))
            <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.add_book.post') }}">
            @csrf
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">书名 *</label>
                <input type="text" id="title" name="title" required autofocus
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            </div>

            <div class="mb-4">
                <label for="author" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">作者 *</label>
                <input type="text" id="author" name="author" required
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            </div>

            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">简介</label>
                <textarea id="description" name="description" rows="4"
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">分类</label>
                    <input type="text" id="category" name="category"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
                <div>
                    <label for="publisher" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">出版社</label>
                    <input type="text" id="publisher" name="publisher"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="page_count" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">页数</label>
                    <input type="number" id="page_count" name="page_count" min="0"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
                <div>
                    <label for="file_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">文件格式</label>
                    <select id="file_format" name="file_format" 
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="">请选择</option>
                        <option value="PDF">PDF</option>
                        <option value="EPUB">EPUB</option>
                        <option value="MOBI">MOBI</option>
                        <option value="AZW3">AZW3</option>
                        <option value="TXT">TXT</option>
                        <option value="ZIP">ZIP</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label for="file_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">文件名</label>
                <input type="text" id="file_name" name="file_name"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            </div>

            <div class="mb-4">
                <label for="file_path" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">文件路径</label>
                <input type="text" id="file_path" name="file_path"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            </div>

            <div class="mb-4">
                <label for="file_size" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">文件大小</label>
                <input type="text" id="file_size" name="file_size" placeholder="例如: 2.5 MB"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
            </div>

            <div class="mb-6">
                <label for="tags" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">标签</label>
                <input type="text" id="tags" name="tags" placeholder="多个标签用逗号分隔，例如: 小说,文学,经典"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">多个标签用逗号分隔</p>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white py-3 rounded-lg transition-all font-medium shadow-md hover:shadow-lg">
                    添加电子书
                </button>
                <a href="{{ route('admin.books') }}" class="px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-all font-medium">
                    取消
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

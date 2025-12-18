@extends('admin.layout')

@section('content')
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('admin.tags') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-all">
            <i class="fas fa-arrow-left mr-2"></i>返回标签列表
        </a>
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100">
            标签：<span class="px-3 py-1 text-lg bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full">{{ $tag_name }}</span>
        </h2>
    </div>

    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">共 {{ $tag_books->count() }} 本书</p>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">ID</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">书名</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">作者</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tag_books as $book)
                    <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $book->id }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('book.detail', $book->id) }}" target="_blank" class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 font-medium">
                                {{ $book->title }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $book->author ?? '未知作者' }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.edit_book', $book->id) }}" 
                               class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

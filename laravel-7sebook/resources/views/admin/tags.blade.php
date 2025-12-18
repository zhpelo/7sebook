@extends('admin.layout')

@section('content')
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100">标签管理</h2>
        <div class="flex gap-2">
            <a href="{{ route('admin.merge_tags') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-lg transition-all text-sm font-medium shadow-md">
                <i class="fas fa-compress-alt mr-2"></i>合并标签
            </a>
            <a href="{{ route('admin.add_tag') }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white rounded-lg transition-all text-sm font-medium shadow-md hover:shadow-lg">
                <i class="fas fa-plus mr-2"></i>添加标签
            </a>
        </div>
    </div>

    <!-- 搜索表单 -->
    <form method="GET" action="{{ route('admin.tags') }}" class="mb-6">
        <div class="flex gap-2">
            <input type="text" name="search" value="{{ $tag_search }}" 
                   placeholder="搜索标签名称..." 
                   class="flex-1 px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-sm shadow-sm text-gray-900 dark:text-gray-100">
            <button type="submit" class="px-4 py-2.5 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-lg transition-all shadow-md">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </form>

    <!-- 标签列表 -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">标签名称</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">使用次数</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tags as $tag)
                    <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-4 py-3">
                            <span class="px-3 py-1 text-sm bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full font-medium">
                                {{ $tag['tag_name'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $tag['usage_count'] }} 本</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('admin.view_tag_books', ['tag' => $tag['tag_name']]) }}" 
                                   class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.edit_tag', ['tag' => $tag['tag_name']]) }}" 
                                   class="p-2 text-yellow-600 dark:text-yellow-400 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 rounded-lg transition-all">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ route('admin.delete_tag', ['tag' => $tag['tag_name']]) }}" 
                                   class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all"
                                   onclick="return confirm('确定要删除标签 {{ $tag['tag_name'] }} 吗？')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- 分页 -->
    @if($total_pages > 1)
        <div class="flex justify-center items-center space-x-2 mt-6">
            @if($page > 1)
                <a href="{{ route('admin.tags', array_merge(request()->all(), ['page' => $page - 1])) }}" 
                   class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all text-sm shadow-sm">
                    <i class="fas fa-chevron-left"></i>
                </a>
            @endif
            
            @for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++)
                <a href="{{ route('admin.tags', array_merge(request()->all(), ['page' => $i])) }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $page == $i ? 'bg-red-600 dark:bg-red-700 text-white shadow-md' : 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }} active:scale-95 shadow-sm">
                    {{ $i }}
                </a>
            @endfor
            
            @if($page < $total_pages)
                <a href="{{ route('admin.tags', array_merge(request()->all(), ['page' => $page + 1])) }}" 
                   class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all text-sm shadow-sm">
                    <i class="fas fa-chevron-right"></i>
                </a>
            @endif
        </div>
    @endif

    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400 text-center">共 {{ $total_tags }} 个标签，第 {{ $page }} / {{ $total_pages }} 页</p>
</div>
@endsection

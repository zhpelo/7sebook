@extends('admin.layout')

@section('content')
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100">书籍管理</h2>
        <a href="{{ route('admin.add_book') }}" class="w-full sm:w-auto px-4 py-2 bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white rounded-lg transition-all text-sm font-medium shadow-md hover:shadow-lg">
            <i class="fas fa-plus mr-2"></i>添加书籍
        </a>
    </div>

    <!-- 搜索 / 筛选 / 排序 -->
    <form method="GET" action="{{ route('admin.books') }}" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <input type="text" name="search" value="{{ $book_search }}" 
                   placeholder="搜索书名或作者..." 
                   class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-sm shadow-sm text-gray-900 dark:text-gray-100">

            <select name="file_format" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-sm shadow-sm text-gray-900 dark:text-gray-100">
                <option value="">全部文件类型</option>
                @foreach($file_formats as $format)
                    <option value="{{ $format }}" {{ $file_format === $format ? 'selected' : '' }}>{{ strtoupper($format) }}</option>
                @endforeach
            </select>

            <div class="grid grid-cols-2 gap-2">
                <input type="number" name="size_min" min="0" step="0.1" value="{{ $size_min }}"
                       placeholder="最小(MB)"
                       class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-sm shadow-sm text-gray-900 dark:text-gray-100">
                <input type="number" name="size_max" min="0" step="0.1" value="{{ $size_max }}"
                       placeholder="最大(MB)"
                       class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-sm shadow-sm text-gray-900 dark:text-gray-100">
            </div>

            <select name="sort_by" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-sm shadow-sm text-gray-900 dark:text-gray-100">
                <option value="created_at" {{ $sort_by === 'created_at' ? 'selected' : '' }}>按添加时间</option>
                <option value="updated_at" {{ $sort_by === 'updated_at' ? 'selected' : '' }}>按更新时间</option>
                <option value="file_size" {{ $sort_by === 'file_size' ? 'selected' : '' }}>按文件大小</option>
            </select>

            <select name="sort_direction" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-sm shadow-sm text-gray-900 dark:text-gray-100">
                <option value="desc" {{ $sort_direction === 'desc' ? 'selected' : '' }}>降序</option>
                <option value="asc" {{ $sort_direction === 'asc' ? 'selected' : '' }}>升序</option>
            </select>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2.5 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-lg transition-all shadow-md text-sm font-medium">
                    <i class="fas fa-search mr-1"></i>查询
                </button>
                <a href="{{ route('admin.books') }}" class="px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all text-sm text-gray-700 dark:text-gray-300 shadow-sm">
                    重置
                </a>
            </div>
        </div>
    </form>

    <!-- 书籍列表 -->
    <form id="batchDeleteForm" method="GET" action="{{ route('admin.batch_delete_books') }}" class="mb-3">
        <input type="hidden" name="ids" id="batchDeleteIds" value="">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" id="batchDeleteBtn" disabled
                class="px-4 py-2 bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white rounded-lg transition-all text-sm font-medium shadow-md disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-red-600 disabled:dark:hover:bg-red-700">
            <i class="fas fa-trash mr-1"></i>批量删除
        </button>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">
                        <input type="checkbox" id="selectAllBooks" class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
                    </th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">封面</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">书名</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">作者</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">格式</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">大小</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach($books as $book)
                    <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-4 py-3">
                            <input type="checkbox" class="book-checkbox w-4 h-4 rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700" value="{{ $book->id }}">
                        </td>
                        <td class="px-4 py-3">
                            <div class="w-12 h-16 bg-gray-100 dark:bg-gray-700 rounded overflow-hidden">
                                @if($book->cover_url)
                                    <img src="{{ Storage::url($book->cover_url) }}" alt="{{ $book->title }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                        <i class="fas fa-book"></i>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900 dark:text-gray-100 max-w-xs truncate">{{ $book->title }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $book->author ?: '未知作者' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded">{{ $book->file_format }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ \App\Helpers\FileHelper::formatFileSize($book->file_size) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('admin.edit_book', $book->id) }}" 
                                   class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ route('admin.delete_book', $book->id) }}" 
                                   class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all"
                                   onclick="return confirm('确定要删除这本书吗？')">
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
                <a href="{{ route('admin.books', array_merge($pagination_query, ['page' => $page - 1])) }}" 
                   class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all text-sm shadow-sm">
                    <i class="fas fa-chevron-left"></i>
                </a>
            @endif
            
            @for($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++)
                <a href="{{ route('admin.books', array_merge($pagination_query, ['page' => $i])) }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $page == $i ? 'bg-red-600 dark:bg-red-700 text-white shadow-md' : 'bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }} active:scale-95 shadow-sm">
                    {{ $i }}
                </a>
            @endfor
            
            @if($page < $total_pages)
                <a href="{{ route('admin.books', array_merge($pagination_query, ['page' => $page + 1])) }}" 
                   class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 transition-all text-sm shadow-sm">
                    <i class="fas fa-chevron-right"></i>
                </a>
            @endif
        </div>
    @endif

    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400 text-center">共 {{ $total_books }} 本书，第 {{ $page }} / {{ $total_pages }} 页</p>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('selectAllBooks');
        const checkboxes = Array.from(document.querySelectorAll('.book-checkbox'));
        const batchDeleteForm = document.getElementById('batchDeleteForm');
        const batchDeleteBtn = document.getElementById('batchDeleteBtn');
        const batchDeleteIds = document.getElementById('batchDeleteIds');

        function getCheckedIds() {
            return checkboxes.filter((item) => item.checked).map((item) => item.value);
        }

        function syncBatchState() {
            const checkedIds = getCheckedIds();
            batchDeleteBtn.disabled = checkedIds.length === 0;

            if (selectAll) {
                selectAll.checked = checkedIds.length > 0 && checkedIds.length === checkboxes.length;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checkboxes.forEach((item) => {
                    item.checked = selectAll.checked;
                });
                syncBatchState();
            });
        }

        checkboxes.forEach((item) => {
            item.addEventListener('change', syncBatchState);
        });

        if (batchDeleteForm) {
            batchDeleteForm.addEventListener('submit', function (event) {
                const checkedIds = getCheckedIds();

                if (checkedIds.length === 0) {
                    event.preventDefault();
                    alert('请先选择要删除的书籍');
                    return;
                }

                if (!confirm('确定要批量删除选中的书籍吗？')) {
                    event.preventDefault();
                    return;
                }

                batchDeleteIds.value = checkedIds.join(',');
            });
        }

        syncBatchState();
    });
</script>
@endpush

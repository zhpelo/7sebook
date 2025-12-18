<!-- 书籍管理 -->
<div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h2 class="text-2xl font-bold text-gray-800">书籍管理</h2>
        <a href="?action=add_book" class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
            <i class="fas fa-plus mr-2"></i> 添加电子书
        </a>
    </div>

    <!-- 搜索和批量操作栏 -->
    <div class="mb-4 flex flex-col sm:flex-row gap-3">
        <form method="GET" class="flex-1 flex gap-2">
            <input type="hidden" name="action" value="books">
            <input type="text" name="search" value="<?php echo htmlspecialchars($book_search ?? ''); ?>" 
                placeholder="搜索书名或作者..." 
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-search"></i>
            </button>
            <?php if (!empty($book_search)): ?>
                <a href="?action=books" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    <i class="fas fa-times"></i>
                </a>
            <?php endif; ?>
        </form>
        <div id="batchActions" class="hidden flex gap-2">
            <a href="#" data-action="batch-delete-books" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                <i class="fas fa-trash mr-2"></i> 批量删除
            </a>
            <a href="#" data-action="clear-selection" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                取消选择
            </a>
        </div>
    </div>

    <!-- 表格 -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">标题</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">作者</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">更新时间</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">格式</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">大小</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($books)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            <?php echo !empty($book_search) ? '未找到匹配的电子书' : '暂无电子书'; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($books as $book): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <input type="checkbox" name="book_ids[]" value="<?php echo $book['id']; ?>" 
                                    class="book-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" 
                                    onchange="updateBatchActions()">
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $book['id']; ?></td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($book['title']); ?>">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($book['author']); ?>">
                                    <?php echo htmlspecialchars($book['author'] ?: '未知'); ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">
                                <?php echo htmlspecialchars($book['updated_at']); ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?php echo strtoupper(htmlspecialchars($book['file_format'])); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 hidden lg:table-cell">
                                <?php 
                                echo \App\Helper\StringHelper::formatFileSize($book['file_size'] ?? 0); 
                                ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    <a href="?action=edit_book&id=<?php echo $book['id']; ?>" 
                                        class="text-green-600 hover:text-green-900 p-1" title="编辑">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?action=delete_book&id=<?php echo $book['id']; ?>" 
                                        class="text-red-600 hover:text-red-900 p-1 inline-flex items-center justify-center cursor-pointer rounded hover:bg-red-50 transition-colors"
                                        title="删除"
                                        onclick="return confirm('确定要删除此电子书吗？');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 分页 -->
    <?php if ($total_pages > 1): ?>
        <?php
        $base_params = ['action' => 'books'];
        $additional_params = [];
        if (!empty($book_search)) {
            $additional_params['search'] = $book_search;
        }
        echo \App\Helper\ViewHelper::renderPagination($page, $total_pages, $total_books, $limit, [
            'base_params' => array_merge($base_params, $additional_params),
            'preserve_params' => []
        ]);
        ?>
    <?php endif; ?>
</div>

<!-- 编辑电子书页面 -->
<div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h2 class="text-2xl font-bold text-gray-800">编辑电子书</h2>
        <a href="?action=books" class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
            <i class="fas fa-arrow-left mr-2"></i> 返回列表
        </a>
    </div>

    <form method="POST" class="max-w-3xl" action="?action=edit_book&id=<?php echo $book['id']; ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">标题 <span class="text-red-500">*</span></label>
                <input type="text" name="title" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['title']); ?>" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">作者 <span class="text-red-500">*</span></label>
                <input type="text" name="author" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['author']); ?>" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">简介</label>
            <textarea name="description" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="4"><?php echo htmlspecialchars($book['description'] ?? ''); ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">类别</label>
                <input type="text" name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['category'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">出版社</label>
                <input type="text" name="publisher" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['publisher'] ?? ''); ?>">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">页数</label>
                <input type="number" name="page_count" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo $book['page_count'] ?? 0; ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">文件格式</label>
                <input type="text" name="file_format" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['file_format'] ?? ''); ?>">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">文件名</label>
                <input type="text" name="file_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['file_name'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">文件大小</label>
                <input type="text" name="file_size" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['file_size'] ?? ''); ?>">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">文件路径</label>
            <input type="text" name="file_path" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['file_path'] ?? ''); ?>">
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">标签（逗号分隔）</label>
            <input type="text" name="tags" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['tags'] ?? ''); ?>" placeholder="标签1,标签2,标签3">
            <p class="text-xs text-gray-500 mt-1">多个标签请用逗号分隔</p>
        </div>

        <div class="flex flex-col sm:flex-row justify-end gap-2">
            <a href="?action=books" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors text-center">取消</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">更新电子书</button>
        </div>
    </form>
</div>

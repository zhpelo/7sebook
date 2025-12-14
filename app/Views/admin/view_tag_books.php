<!-- 查看标签关联书籍页面 -->
<div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h2 class="text-2xl font-bold text-gray-800">标签 "<?php echo htmlspecialchars($tag_name); ?>" 关联的书籍</h2>
        <a href="?action=tags" class="w-full sm:w-auto px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center">
            <i class="fas fa-arrow-left mr-2"></i> 返回列表
        </a>
    </div>
    <div class="border rounded-lg overflow-hidden">
        <?php if (empty($tag_books)): ?>
            <div class="px-4 py-8 text-center text-gray-500">暂无关联书籍</div>
        <?php else: ?>
            <?php foreach ($tag_books as $book): ?>
                <a href="?action=edit_book&id=<?php echo $book['id']; ?>" target="_blank" class="block py-2 px-4 hover:bg-gray-100 border-b transition-colors">
                    <?php echo htmlspecialchars($book['title']); ?> - <?php echo htmlspecialchars($book['author']); ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

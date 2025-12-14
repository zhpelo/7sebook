<!-- 删除用户确认页面 -->
<div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6 max-w-md mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">确认删除用户</h2>
    <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded mb-4">
        <p class="font-semibold">警告：此操作不可恢复！</p>
        <p class="text-sm mt-1">确定要删除用户 "<?php echo htmlspecialchars($delete_user['username']); ?>" 吗？</p>
    </div>
    <div class="flex justify-end gap-2">
        <a href="?action=users" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">取消</a>
        <a href="?action=delete_user&id=<?php echo $delete_user['id']; ?>&confirm=yes" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">确认删除</a>
    </div>
</div>

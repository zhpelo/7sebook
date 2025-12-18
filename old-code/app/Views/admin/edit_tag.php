<!-- 编辑标签页面 -->
<div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h2 class="text-2xl font-bold text-gray-800">编辑标签</h2>
        <a href="?action=tags" class="w-full sm:w-auto px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center">
            <i class="fas fa-arrow-left mr-2"></i> 返回列表
        </a>
    </div>
    <form method="POST" action="?action=edit_tag&tag=<?php echo urlencode($old_tag_name); ?>" class="max-w-md">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">新标签名称 <span class="text-red-500">*</span></label>
            <input type="text" name="new_tag_name" value="<?php echo htmlspecialchars($old_tag_name); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
            <p class="text-xs text-gray-500 mt-1">如果新标签名已存在，系统会自动合并</p>
        </div>
        <div class="flex justify-end gap-2">
            <a href="?action=tags" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">取消</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">保存</button>
        </div>
    </form>
</div>

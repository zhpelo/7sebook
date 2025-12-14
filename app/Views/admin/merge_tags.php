<!-- 合并标签页面 -->
<div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h2 class="text-2xl font-bold text-gray-800">合并标签</h2>
        <a href="?action=tags" class="w-full sm:w-auto px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center">
            <i class="fas fa-arrow-left mr-2"></i> 返回列表
        </a>
    </div>
    <form method="POST" action="?action=merge_tags" class="max-w-md">
        <div class="space-y-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">源标签（将被合并的标签） <span class="text-red-500">*</span></label>
                <input type="text" name="source_tag" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="输入要合并的标签名称" required>
                <p class="text-xs text-gray-500 mt-1">该标签将被删除，其所有关联将转移到目标标签</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">目标标签（合并到的标签） <span class="text-red-500">*</span></label>
                <input type="text" name="target_tag" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="输入目标标签名称" required>
                <p class="text-xs text-gray-500 mt-1">源标签的所有关联将转移到此标签</p>
            </div>
        </div>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 p-4 rounded mb-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span class="font-semibold">警告：</span>
            </div>
            <p class="text-sm mt-1">此操作不可逆，请确认源标签和目标标签名称正确！</p>
        </div>
        <div class="flex justify-end gap-2">
            <a href="?action=tags" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">取消</a>
            <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">确认合并</button>
        </div>
    </form>
</div>

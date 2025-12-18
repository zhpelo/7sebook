<!-- 添加标签页面 -->
<div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">添加标签</h2>
            <p class="text-sm text-gray-500 mt-1">为书籍添加新标签</p>
        </div>
        <a href="?action=tags" class="w-full sm:w-auto px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center">
            <i class="fas fa-arrow-left mr-2"></i> 返回列表
        </a>
    </div>
    <form method="POST" action="?action=add_tag" class="max-w-2xl" id="addTagForm">
        <div class="space-y-5 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">标签名称 <span class="text-red-500">*</span></label>
                <input type="text" name="tag_name" id="tag_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="输入标签名称，如：科幻、历史、文学等" required>
                <p class="text-xs text-gray-500 mt-1">标签名称将用于分类和搜索书籍</p>
            </div>

            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="auto_search" id="auto_search" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm font-medium text-gray-700">启用自动搜索添加功能</span>
                </label>
                <p class="text-xs text-gray-500 mt-2 ml-6">
                    <i class="fas fa-info-circle mr-1"></i>
                    勾选后，系统将自动搜索书籍表中标题或作者包含此标签名称的书籍，并全部加入此标签
                </p>
            </div>

            <div id="book_id_field">
                <label class="block text-sm font-medium text-gray-700 mb-1">图书ID</label>
                <input type="number" name="book_id" id="book_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="如果不使用自动搜索，请输入图书ID">
                <p class="text-xs text-gray-500 mt-1">手动指定要添加标签的图书ID（仅在未启用自动搜索时使用）</p>
            </div>
        </div>

        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-lightbulb text-blue-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        <strong>提示：</strong>启用自动搜索功能后，系统会在书籍的标题和作者字段中搜索包含标签名称的内容。例如，标签"科幻"会匹配所有标题或作者中包含"科幻"二字的书籍。
                    </p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <a href="?action=tags" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">取消</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i> 添加标签
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const autoSearchCheckbox = document.getElementById('auto_search');
            const bookIdField = document.getElementById('book_id_field');
            const bookIdInput = document.getElementById('book_id');
            const form = document.getElementById('addTagForm');

            // 切换自动搜索功能时显示/隐藏图书ID字段
            autoSearchCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    bookIdField.style.display = 'none';
                    bookIdInput.removeAttribute('required');
                    bookIdInput.value = '';
                } else {
                    bookIdField.style.display = 'block';
                    bookIdInput.setAttribute('required', 'required');
                }
            });

            // 表单提交前的验证
            form.addEventListener('submit', function(e) {
                if (!autoSearchCheckbox.checked && !bookIdInput.value.trim()) {
                    e.preventDefault();
                    alert("请启用自动搜索功能或输入图书ID");
                    return false;
                }
            });
        });
    </script>
</div>

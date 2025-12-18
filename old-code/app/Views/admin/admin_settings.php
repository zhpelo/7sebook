<!-- 管理员设置 -->
<div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">管理员设置</h2>

    <form method="POST" class="max-w-md" action="?action=admin_settings">
        <input type="hidden" name="admin_id" value="<?php echo $admin_user['id']; ?>">

        <div class="space-y-4 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">用户名</label>
                <input type="text" name="username" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($admin_user['username']); ?>" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">邮箱</label>
                <input type="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($admin_user['email']); ?>" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">新密码（留空则不修改）</label>
                <input type="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="留空则不修改密码">
                <p class="text-xs text-gray-500 mt-1">如果不修改密码，请留空此字段</p>
            </div>
        </div>

        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
            <i class="fas fa-save mr-2"></i> 更新管理员信息
        </button>
    </form>
</div>

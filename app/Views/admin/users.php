<!-- 用户管理 -->
<div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h2 class="text-2xl font-bold text-gray-800">用户管理</h2>
        <a href="?action=add_user" class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
            <i class="fas fa-plus mr-2"></i> 添加用户
        </a>
    </div>

    <!-- 搜索和批量操作栏 -->
    <div class="mb-4 flex flex-col sm:flex-row gap-3">
        <form method="GET" class="flex-1 flex gap-2">
            <input type="hidden" name="action" value="users">
            <input type="text" name="search" value="<?php echo htmlspecialchars($user_search ?? ''); ?>" 
                placeholder="搜索用户名或邮箱..." 
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-search"></i>
            </button>
            <?php if (!empty($user_search)): ?>
                <a href="?action=users" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    <i class="fas fa-times"></i>
                </a>
            <?php endif; ?>
        </form>
        <div id="batchActions" class="hidden flex gap-2">
            <a href="#" data-action="batch-delete-users" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
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
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用户名</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">邮箱</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">角色</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">创建时间</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <?php echo !empty($user_search) ? '未找到匹配的用户' : '暂无用户'; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <input type="checkbox" name="user_ids[]" value="<?php echo $user['id']; ?>" 
                                    class="user-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" 
                                    onchange="updateBatchActions()">
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $user['id']; ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo $user['role'] === 'admin' ? '管理员' : '普通用户'; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell"><?php echo $user['created_at']; ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    <a href="?action=edit_user&id=<?php echo $user['id']; ?>" 
                                        class="text-green-600 hover:text-green-900 p-1 inline-flex items-center justify-center cursor-pointer rounded hover:bg-green-50 transition-colors"
                                        title="编辑用户">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?action=delete_user&id=<?php echo $user['id']; ?>" 
                                        class="text-red-600 hover:text-red-900 p-1 inline-flex items-center justify-center cursor-pointer rounded hover:bg-red-50 transition-colors"
                                        title="删除用户"
                                        onclick="return confirm('确定要删除此用户吗？');">
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
        $base_params = ['action' => 'users'];
        $additional_params = [];
        if (!empty($user_search)) {
            $additional_params['search'] = $user_search;
        }
        echo \App\Helper\ViewHelper::renderPagination($page, $total_pages, $total_users, $limit, [
            'base_params' => array_merge($base_params, $additional_params),
            'preserve_params' => []
        ]);
        ?>
    <?php endif; ?>
</div>

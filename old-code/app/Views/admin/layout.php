<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : '七秒电子书后台管理'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    transitionProperty: {
                        'width': 'width',
                        'transform': 'transform',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- 移动端遮罩层 -->
    <a href="#" data-action="toggle-sidebar" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden transition-opacity duration-300" id="overlay"></a>

    <!-- 侧边栏 -->
    <aside id="adminSidebar" class="fixed top-0 left-0 h-screen w-64 bg-gray-800 text-white z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out overflow-y-auto">
        <div class="p-6 border-b border-gray-700">
            <h1 class="text-xl font-bold flex items-center">
                <i class="fas fa-book mr-2 text-blue-400"></i>
                <span class="hidden sm:inline">七秒后台</span>
                <span class="sm:hidden">后台</span>
            </h1>
        </div>

        <nav class="p-4 space-y-1">
            <a href="?action=dashboard" class="flex items-center py-3 px-4 rounded-lg transition-colors <?php echo ($action ?? 'dashboard') === 'dashboard' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                <i class="fas fa-tachometer-alt w-5 mr-3"></i>
                <span>仪表盘</span>
            </a>
            <a href="?action=users" class="flex items-center py-3 px-4 rounded-lg transition-colors <?php echo ($action ?? '') === 'users' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                <i class="fas fa-users w-5 mr-3"></i>
                <span>用户管理</span>
            </a>
            <a href="?action=books" class="flex items-center py-3 px-4 rounded-lg transition-colors <?php echo ($action ?? '') === 'books' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                <i class="fas fa-book w-5 mr-3"></i>
                <span>书籍管理</span>
            </a>
            <a href="?action=downloads" class="flex items-center py-3 px-4 rounded-lg transition-colors <?php echo ($action ?? '') === 'downloads' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                <i class="fas fa-download w-5 mr-3"></i>
                <span>下载记录</span>
            </a>
            <a href="?action=search_history" class="flex items-center py-3 px-4 rounded-lg transition-colors <?php echo ($action ?? '') === 'search_history' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                <i class="fas fa-search w-5 mr-3"></i>
                <span>搜索记录</span>
            </a>
            <a href="?action=tags" class="flex items-center py-3 px-4 rounded-lg transition-colors <?php echo ($action ?? '') === 'tags' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                <i class="fas fa-tags w-5 mr-3"></i>
                <span>标签管理</span>
            </a>
            <a href="?action=admin_settings" class="flex items-center py-3 px-4 rounded-lg transition-colors <?php echo ($action ?? '') === 'admin_settings' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                <i class="fas fa-cog w-5 mr-3"></i>
                <span>管理员设置</span>
            </a>
            <a href="index.php" target="_blank" class="flex items-center py-3 px-4 rounded-lg transition-colors text-gray-300 hover:bg-blue-600 hover:text-white mt-4">
                <i class="fas fa-external-link-alt w-5 mr-3"></i>
                <span>访问前台</span>
            </a>
            <a href="?action=logout" class="flex items-center py-3 px-4 rounded-lg transition-colors text-gray-300 hover:bg-red-600 hover:text-white">
                <i class="fas fa-sign-out-alt w-5 mr-3"></i>
                <span>退出登录</span>
            </a>
        </nav>
    </aside>

    <!-- 移动端顶部栏 -->
    <header class="lg:hidden fixed top-0 left-0 right-0 bg-white shadow-md z-30 px-4 py-3 flex items-center justify-between">
        <a href="#" data-action="toggle-sidebar" class="p-2 rounded-lg hover:bg-gray-100">
            <i class="fas fa-bars text-gray-700"></i>
        </a>
        <h1 class="text-lg font-bold text-gray-800">七秒后台</h1>
        <a href="index.php" target="_blank" class="p-2 rounded-lg hover:bg-blue-100 text-blue-600" title="访问前台">
            <i class="fas fa-external-link-alt"></i>
        </a>
    </header>

    <!-- 主内容区域 -->
    <main class="lg:ml-64 pt-16 lg:pt-0 min-h-screen">
        <div class="p-4 lg:p-6">
            <!-- 消息提示 -->
            <?php if (isset($message)): ?>
                <div class="mb-4 bg-green-50 border-l-4 border-green-400 text-green-700 p-4 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="mb-4 bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php echo $content ?? ''; ?>
        </div>
    </main>

    <script>
        // 侧边栏切换
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        // 批量操作相关
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.user-checkbox, .book-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
            updateBatchActions();
        }

        function updateBatchActions() {
            const checkboxes = document.querySelectorAll('.user-checkbox:checked, .book-checkbox:checked');
            const batchActions = document.getElementById('batchActions');
            if (checkboxes.length > 0) {
                batchActions?.classList.remove('hidden');
            } else {
                batchActions?.classList.add('hidden');
            }
        }

        function clearSelection() {
            const checkboxes = document.querySelectorAll('.user-checkbox, .book-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            const selectAll = document.getElementById('selectAll');
            if (selectAll) selectAll.checked = false;
            updateBatchActions();
        }

        function batchDeleteUsers() {
            const checkboxes = document.querySelectorAll('.user-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('请选择要删除的用户');
                return;
            }
            if (!confirm(`确定要删除选中的 ${checkboxes.length} 个用户吗？此操作不可恢复！`)) {
                return;
            }
            const ids = Array.from(checkboxes).map(cb => cb.value).join(',');
            window.location.href = '?action=batch_delete_users&ids=' + ids + '&confirm=yes';
        }

        function batchDeleteBooks() {
            const checkboxes = document.querySelectorAll('.book-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('请选择要删除的电子书');
                return;
            }
            if (!confirm(`确定要删除选中的 ${checkboxes.length} 本电子书吗？此操作不可恢复！`)) {
                return;
            }
            const ids = Array.from(checkboxes).map(cb => cb.value).join(',');
            window.location.href = '?action=batch_delete_books&ids=' + ids + '&confirm=yes';
        }

        // 使用事件委托处理所有链接点击
        document.addEventListener('click', function(e) {
            const target = e.target.closest('a[data-action]');
            if (!target) return;
            
            e.preventDefault();
            e.stopPropagation();
            
            const action = target.getAttribute('data-action');
            
            // 处理侧边栏切换
            if (action === 'toggle-sidebar') {
                toggleSidebar();
                return;
            }
            
            // 处理批量删除用户
            if (action === 'batch-delete-users') {
                batchDeleteUsers();
                return;
            }
            
            // 处理批量删除书籍
            if (action === 'batch-delete-books') {
                batchDeleteBooks();
                return;
            }
            
            // 处理清除选择
            if (action === 'clear-selection') {
                clearSelection();
                return;
            }
        });
    </script>
</body>
</html>

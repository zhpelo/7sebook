<!-- 注册页面 -->
<div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">用户注册</h1>
                <a href="./" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-1"></i> 返回首页
                </a>
            </div>

            <?php if (isset($register_error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($register_error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="register">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="reg_username">用户名</label>
                    <input type="text" name="reg_username" id="reg_username" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="reg_email">邮箱</label>
                    <input type="email" name="reg_email" id="reg_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="reg_password">密码</label>
                    <input type="password" name="reg_password" id="reg_password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required minlength="6" maxlength="32">
                    <p class="text-gray-500 text-xs mt-1">密码长度为6-32个字符</p>
                </div>
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg font-semibold transition">
                    注册
                </button>
            </form>

            <div class="text-center mt-4">
                <p class="text-gray-600">已有账号？</p>
                <a href="?action=login" class="text-blue-600 hover:text-blue-800 font-medium">
                    立即登录
                </a>
            </div>
        </div>
    </div>
</div>

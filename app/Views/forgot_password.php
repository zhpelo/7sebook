<!-- 忘记密码页面 -->
<div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">忘记密码</h1>
                <a href="./" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-1"></i> 返回首页
                </a>
            </div>

            <?php if (isset($forgot_password_error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($forgot_password_error); ?>
                </div>
            <?php elseif (isset($forgot_password_message)): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($forgot_password_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="forgot_password">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="forgot_email">邮箱地址</label>
                    <input type="email" name="email" id="forgot_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-semibold transition">
                    发送重置链接
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="?action=login" class="text-blue-600 hover:text-blue-800 font-medium">
                    返回登录
                </a>
            </div>
        </div>
    </div>
</div>

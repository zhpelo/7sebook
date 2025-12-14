<!-- 重置密码页面 -->
<div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">重置密码</h1>
                <a href="./" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-1"></i> 返回首页
                </a>
            </div>

            <?php if (isset($reset_password_error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($reset_password_error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($reset_password_success)): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($reset_password_success); ?>
                </div>
                <div class="text-center">
                    <a href="./" class="text-blue-600 hover:text-blue-800 font-medium">返回首页</a>
                </div>
            <?php elseif (isset($reset_token) && isset($reset_email)): ?>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($reset_token); ?>">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">邮箱地址</label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($reset_email); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" readonly>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="new_password">新密码</label>
                        <input type="password" name="new_password" id="new_password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required minlength="6" maxlength="32">
                        <p class="text-gray-500 text-xs mt-1">密码长度为6-32个字符</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">确认新密码</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required minlength="6" maxlength="32">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-semibold transition">
                        重置密码
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

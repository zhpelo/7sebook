<?php
use App\Service\BookLikeService;
?>
<!-- 个人资料页面 -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">个人资料</h1>
                <a href="./" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-1"></i> 返回首页
                </a>
            </div>

            <?php if (isset($message)): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">基本信息</h2>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="mb-3">
                            <label class="block text-gray-600 text-sm mb-1">用户名</label>
                            <div class="font-medium"><?php echo htmlspecialchars($user_info['username']); ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="block text-gray-600 text-sm mb-1">注册时间</label>
                            <div class="font-medium"><?php echo htmlspecialchars($user_info['created_at']); ?></div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">我的记录</h2>
                    <div class="bg-gray-50 p-4 rounded-lg space-y-3">
                        <a href="?action=my_likes" class="flex items-center justify-between p-3 bg-white rounded-lg hover:bg-red-50 transition-colors border border-gray-200">
                            <div class="flex items-center">
                                <i class="fas fa-heart text-red-500 mr-3" aria-hidden="true"></i>
                                <span class="text-gray-700 font-medium">我的点赞</span>
                            </div>
                            <span class="text-gray-500 text-sm"><?php echo $liked_count; ?> 本</span>
                        </a>
                        <a href="?action=my_favorites" class="flex items-center justify-between p-3 bg-white rounded-lg hover:bg-yellow-50 transition-colors border border-gray-200">
                            <div class="flex items-center">
                                <i class="fas fa-star text-yellow-500 mr-3" aria-hidden="true"></i>
                                <span class="text-gray-700 font-medium">我的收藏</span>
                            </div>
                            <span class="text-gray-500 text-sm"><?php echo $favorited_count; ?> 本</span>
                        </a>
                    </div>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">修改资料</h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="update_profile">

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">邮箱地址</label>
                            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="current_password">当前密码 *</label>
                            <input type="password" name="current_password" id="current_password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="new_password">新密码</label>
                            <input type="password" name="new_password" id="new_password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-gray-500 text-xs mt-1">6-32个字符，留空则不更改</p>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">确认新密码</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-semibold transition">
                                更新资料
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

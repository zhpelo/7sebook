<?php
use App\Helper\ViewHelper;
?>
<!-- 我的点赞记录页面 -->
<div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-4 sm:py-6 lg:py-8">
    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                <i class="fas fa-heart text-red-500 mr-2" aria-hidden="true"></i>我的点赞
            </h1>
            <div class="flex items-center gap-3">
                <a href="?action=profile" class="text-blue-600 hover:text-blue-800 text-sm sm:text-base">
                    <i class="fas fa-user mr-1" aria-hidden="true"></i>个人资料
                </a>
                <a href="./" class="text-blue-600 hover:text-blue-800 text-sm sm:text-base">
                    <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i>返回首页
                </a>
            </div>
        </div>
        
        <?php if (empty($user_liked_books)): ?>
            <div class="text-center py-12 sm:py-20">
                <i class="fas fa-heart text-5xl sm:text-6xl text-gray-300 mb-4" aria-hidden="true"></i>
                <h3 class="text-lg sm:text-xl font-semibold text-gray-600 mb-2">暂无点赞记录</h3>
                <p class="text-sm sm:text-base text-gray-500">您还没有点赞过任何书籍</p>
            </div>
        <?php else: ?>
            <div class="mb-4">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                    <p class="text-sm sm:text-base text-gray-600">
                        共找到 <span class="font-bold text-red-600"><?php echo $user_liked_total; ?></span> 本点赞的书籍
                    </p>
                    <!-- 布局切换按钮 -->
                    <div class="flex items-center gap-1 sm:gap-2 bg-gray-100 rounded-lg p-0.5 sm:p-1" role="group" aria-label="切换布局">
                        <button 
                            onclick="switchBookLayout('grid', 'myLikesList')" 
                            id="likesGridBtn" 
                            class="px-2 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition min-h-[36px] sm:min-h-[40px] focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $book_layout === 'grid' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-200'; ?>" 
                            aria-label="网格布局"
                            aria-pressed="<?php echo $book_layout === 'grid' ? 'true' : 'false'; ?>">
                            <i class="fas fa-th mr-1" aria-hidden="true"></i><span class="hidden xs:inline">方格</span>
                        </button>
                        <button 
                            onclick="switchBookLayout('list', 'myLikesList')" 
                            id="likesListBtn" 
                            class="px-2 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition min-h-[36px] sm:min-h-[40px] focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $book_layout === 'list' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-200'; ?>" 
                            aria-label="列表布局"
                            aria-pressed="<?php echo $book_layout === 'list' ? 'true' : 'false'; ?>">
                            <i class="fas fa-list mr-1" aria-hidden="true"></i><span class="hidden xs:inline">列表</span>
                        </button>
                    </div>
                </div>
            </div>
            <?php echo ViewHelper::renderBooksList($user_liked_books, $book_layout, '', 'myLikesList'); ?>
            <?php if ($total_liked_pages > 1): ?>
                <div class="mt-6">
                    <?php echo ViewHelper::renderPagination($page, $total_liked_pages, $user_liked_total, 20, function($new_page) { return "?action=my_likes&page=$new_page"; }); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

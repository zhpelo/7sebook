<?php
use App\Helper\ViewHelper;
?>
<!-- 最近更新页面 -->
<div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-4 sm:py-6 lg:py-8">
    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                <i class="fas fa-clock text-blue-500 mr-2" aria-hidden="true"></i>最近更新
            </h1>
            <a href="./" class="text-blue-600 hover:text-blue-800 text-sm sm:text-base">
                <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i>返回首页
            </a>
        </div>
        
        <?php if (empty($latest_books)): ?>
            <div class="text-center py-12 sm:py-20">
                <i class="fas fa-book text-5xl sm:text-6xl text-gray-300 mb-4" aria-hidden="true"></i>
                <h3 class="text-lg sm:text-xl font-semibold text-gray-600 mb-2">暂无更新</h3>
                <p class="text-sm sm:text-base text-gray-500">暂时没有新的书籍</p>
            </div>
        <?php else: ?>
            <div class="mb-4">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                    <p class="text-sm sm:text-base text-gray-600">
                        共找到 <span class="font-bold text-blue-600"><?php echo $latest_books_total; ?></span> 本图书
                    </p>
                    <!-- 布局切换按钮 -->
                    <div class="flex items-center gap-1 sm:gap-2 bg-gray-100 rounded-lg p-0.5 sm:p-1" role="group" aria-label="切换布局">
                        <button 
                            onclick="switchBookLayout('grid', 'latestBooksList')" 
                            id="latestGridBtn" 
                            class="px-2 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition min-h-[36px] sm:min-h-[40px] focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $book_layout === 'grid' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-200'; ?>" 
                            aria-label="网格布局"
                            aria-pressed="<?php echo $book_layout === 'grid' ? 'true' : 'false'; ?>">
                            <i class="fas fa-th mr-1" aria-hidden="true"></i><span class="hidden xs:inline">方格</span>
                        </button>
                        <button 
                            onclick="switchBookLayout('list', 'latestBooksList')" 
                            id="latestListBtn" 
                            class="px-2 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition min-h-[36px] sm:min-h-[40px] focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $book_layout === 'list' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-200'; ?>" 
                            aria-label="列表布局"
                            aria-pressed="<?php echo $book_layout === 'list' ? 'true' : 'false'; ?>">
                            <i class="fas fa-list mr-1" aria-hidden="true"></i><span class="hidden xs:inline">列表</span>
                        </button>
                    </div>
                </div>
            </div>
            <?php echo ViewHelper::renderBooksList($latest_books, $book_layout, '', 'latestBooksList'); ?>
            <?php if ($total_latest_pages > 1): ?>
                <div class="mt-6">
                    <?php echo ViewHelper::renderPagination($page, $total_latest_pages, $latest_books_total, 20, function($new_page) { return "?action=latest&page=$new_page"; }); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

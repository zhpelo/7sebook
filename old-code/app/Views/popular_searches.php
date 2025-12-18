<!-- 热门搜索页面 -->
<div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-4 sm:py-6 lg:py-8">
    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                <i class="fas fa-fire text-orange-500 mr-2" aria-hidden="true"></i>热门搜索
            </h1>
            <a href="./" class="text-blue-600 hover:text-blue-800 text-sm sm:text-base">
                <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i>返回首页
            </a>
        </div>
        
        <?php if (empty($popular_searches_list)): ?>
            <div class="text-center py-12 sm:py-20">
                <i class="fas fa-search text-5xl sm:text-6xl text-gray-300 mb-4" aria-hidden="true"></i>
                <h3 class="text-lg sm:text-xl font-semibold text-gray-600 mb-2">暂无搜索记录</h3>
                <p class="text-sm sm:text-base text-gray-500">搜索记录将在用户搜索后显示</p>
            </div>
        <?php else: ?>
            <div class="mb-4">
                <p class="text-sm sm:text-base text-gray-600">
                    共找到 <span class="font-bold text-blue-600"><?php echo count($popular_searches_list); ?></span> 个热门搜索关键词
                </p>
            </div>
            <div class="flex flex-wrap gap-2 sm:gap-3">
                <?php foreach ($popular_searches_list as $idx => $popular): ?>
                    <a href="?q=<?php echo urlencode($popular['search_query']); ?>" 
                       class="inline-flex items-center px-3 sm:px-4 py-2 sm:py-2.5 bg-gradient-to-r from-blue-50 to-purple-50 hover:from-blue-100 hover:to-purple-100 border border-blue-200 rounded-lg text-sm sm:text-base text-gray-700 hover:text-blue-700 transition-all duration-200 shadow-sm hover:shadow-md">
                        <span class="inline-flex items-center justify-center w-6 h-6 sm:w-7 sm:h-7 bg-blue-500 text-white rounded-full text-xs sm:text-sm font-bold mr-2 sm:mr-3">
                            <?php echo $idx + 1; ?>
                        </span>
                        <span class="font-medium"><?php echo htmlspecialchars($popular['search_query']); ?></span>
                        <span class="ml-2 sm:ml-3 text-xs sm:text-sm text-gray-500 bg-white px-2 py-0.5 rounded-full">
                            <?php echo number_format($popular['search_count']); ?>次
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

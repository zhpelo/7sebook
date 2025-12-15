<?php
use App\Helper\StringHelper;
use App\Helper\ViewHelper;
?>
<!-- 下载排行页面 -->
<div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-4 sm:py-6 lg:py-8">
    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">
                <i class="fas fa-trophy text-yellow-500 mr-2" aria-hidden="true"></i>下载排行
            </h1>
            <div class="flex items-center gap-3">
                <a href="./" class="text-blue-600 hover:text-blue-800 text-sm sm:text-base">
                    <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i>返回首页
                </a>
                <!-- 布局切换按钮 -->
                <div class="flex items-center gap-1 sm:gap-2 bg-gray-100 rounded-lg p-0.5 sm:p-1" role="group" aria-label="切换布局">
                    <button 
                        onclick="switchBookLayout('grid', 'downloadRankingList')" 
                        id="rankingGridBtn" 
                        class="px-2 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition min-h-[36px] sm:min-h-[40px] focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $book_layout === 'grid' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-200'; ?>" 
                        aria-label="网格布局"
                        aria-pressed="<?php echo $book_layout === 'grid' ? 'true' : 'false'; ?>">
                        <i class="fas fa-th mr-1" aria-hidden="true"></i><span class="hidden xs:inline">方格</span>
                    </button>
                    <button 
                        onclick="switchBookLayout('list', 'downloadRankingList')" 
                        id="rankingListBtn" 
                        class="px-2 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition min-h-[36px] sm:min-h-[40px] focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $book_layout === 'list' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-200'; ?>" 
                        aria-label="列表布局"
                        aria-pressed="<?php echo $book_layout === 'list' ? 'true' : 'false'; ?>">
                        <i class="fas fa-list mr-1" aria-hidden="true"></i><span class="hidden xs:inline">列表</span>
                    </button>
                </div>
            </div>
        </div>
        
        <?php if (empty($download_ranking_books)): ?>
            <div class="text-center py-12 sm:py-20">
                <i class="fas fa-book text-5xl sm:text-6xl text-gray-300 mb-4" aria-hidden="true"></i>
                <h3 class="text-lg sm:text-xl font-semibold text-gray-600 mb-2">暂无书籍数据</h3>
                <p class="text-sm sm:text-base text-gray-500">系统中还没有书籍数据</p>
            </div>
        <?php else: ?>
            <div class="mb-4">
                <p class="text-sm sm:text-base text-gray-600">
                    共找到 <span class="font-bold text-blue-600"><?php echo count($download_ranking_books); ?></span> 本热门书籍
                </p>
            </div>
            <!-- 书籍列表 -->
            <div id="downloadRankingList" class="books-container" data-layout="<?php echo htmlspecialchars($book_layout); ?>">
                <?php if ($book_layout === 'list'): ?>
                    <!-- 列表布局 -->
                    <div class="space-y-4">
                        <?php foreach ($download_ranking_books as $idx => $book): ?>
                            <a href="?action=detail&id=<?php echo $book['id']; ?>">
                                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 flex mb-6">
                                    <div class="w-20 sm:w-24 flex-shrink-0 flex items-center justify-center bg-yellow-50">
                                        <span class="inline-flex items-center justify-center w-12 h-12 sm:w-14 sm:h-14 bg-yellow-500 text-white rounded-full text-base sm:text-lg font-bold">
                                            <?php echo $idx + 1; ?>
                                        </span>
                                    </div>
                                    <div class="w-32 sm:w-40 flex-shrink-0">
                                        <div class="bg-gray-100 flex items-center justify-center h-full">
                                            <?php if ($book['cover_url']): ?>
                                                <?php $optimizedUrl = \App\Helper\ViewHelper::getOptimizedImageUrl($book['cover_url']); ?>
                                                <img src="<?php echo htmlspecialchars($optimizedUrl); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="w-full h-full object-cover" loading="lazy">
                                            <?php else: ?>
                                                <div class="text-gray-400 text-center p-2">
                                                    <i class="fas fa-book text-2xl mb-1"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex-1 p-4 flex flex-col justify-between">
                                        <div>
                                            <h3 class="font-bold text-lg mb-2"><?php echo htmlspecialchars($book['title']); ?></h3>
                                            <?php if ($book['author']): ?>
                                                <p class="text-gray-600 mb-2">
                                                    <i class="fas fa-user text-sm mr-2"></i>
                                                    <?php echo htmlspecialchars($book['author']); ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if (!empty($book['description'])): ?>
                                                <p class="text-gray-500 text-sm mb-2 line-clamp-4"><?php echo htmlspecialchars(substr($book['description'], 0, 150)); ?><?php echo strlen($book['description']) > 150 ? '...' : ''; ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex items-center justify-between mt-2">
                                            <div class="flex gap-2 items-center text-xs">
                                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded"><?php echo htmlspecialchars($book['file_format']); ?></span>
                                                <span class="text-gray-500"><?php echo \App\Helper\StringHelper::formatFileSize($book['file_size']); ?></span>
                                            </div>
                                            <div class="flex items-center gap-2 text-sm sm:text-base">
                                                <i class="fas fa-download text-blue-500" aria-hidden="true"></i>
                                                <span class="font-bold text-blue-600"><?php echo number_format($book['download_count'] ?? 0); ?></span>
                                                <span class="text-gray-500 text-xs">次</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- 方格布局 -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                        <?php foreach ($download_ranking_books as $idx => $book): ?>
                            <a href="?action=detail&id=<?php echo $book['id']; ?>">
                                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 relative">
                                    <div class="absolute top-2 left-2 z-10">
                                        <span class="inline-flex items-center justify-center w-8 h-8 bg-yellow-500 text-white rounded-full text-sm font-bold shadow-md">
                                            <?php echo $idx + 1; ?>
                                        </span>
                                    </div>
                                    <div class="bg-gray-100 flex items-center justify-center aspect-[2/3]">
                                        <?php if ($book['cover_url']): ?>
                                            <?php $optimizedUrl = \App\Helper\ViewHelper::getOptimizedImageUrl($book['cover_url']); ?>
                                            <img src="<?php echo htmlspecialchars($optimizedUrl); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="w-full h-full object-cover" loading="lazy">
                                        <?php else: ?>
                                            <div class="text-gray-400 text-center p-4">
                                                <i class="fas fa-book text-4xl mb-2"></i>
                                                <div class="text-sm"><?php echo htmlspecialchars($book['title']); ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="p-4">
                                        <h3 class="font-bold text-lg mb-2 line-clamp-2"><?php echo htmlspecialchars($book['title']); ?></h3>
                                        <?php if ($book['author']): ?>
                                            <p class="text-gray-600 mb-2 line-clamp-1">
                                                <i class="fas fa-user text-sm mr-2"></i>
                                                <?php echo htmlspecialchars($book['author']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <div class="flex items-center justify-between mt-2 text-xs">
                                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded"><?php echo htmlspecialchars($book['file_format']); ?></span>
                                            <div class="flex items-center gap-1 text-blue-600">
                                                <i class="fas fa-download" aria-hidden="true"></i>
                                                <span class="font-bold"><?php echo number_format($book['download_count'] ?? 0); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

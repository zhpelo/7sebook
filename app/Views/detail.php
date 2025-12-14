<?php
use App\Helper\StringHelper;
use App\Service\DownloadService;
use App\Helper\NetworkHelper;
use App\Helper\ViewHelper;
use App\Auth\Auth;

$auth = $auth ?? new Auth();
?>
<!-- 详情页 -->
<div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-4 sm:py-6 lg:py-8">
    <article class="bg-white rounded-lg shadow-md overflow-hidden mb-4 sm:mb-6 lg:mb-8" itemscope itemtype="https://schema.org/Book">
        <div class="p-4 sm:p-6">
            <div class="flex flex-col md:flex-row gap-4 sm:gap-6">
                <div class="md:w-1/3 flex-shrink-0 mx-auto md:mx-0 max-w-xs md:max-w-none">
                    <?php if ($book['cover_url']): ?>
                        <div class="bg-gray-100 flex items-center justify-center rounded-lg overflow-hidden" itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
                            <img 
                                src="<?php echo htmlspecialchars($book['cover_url']); ?>" 
                                alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                class="w-full h-auto object-cover rounded-lg"
                                loading="lazy"
                                itemprop="url">
                            <meta itemprop="width" content="300">
                            <meta itemprop="height" content="450">
                        </div>
                    <?php else: ?>
                        <div class="bg-gray-100 flex items-center justify-center rounded-lg aspect-[2/3]">
                            <div class="text-gray-400 text-center p-4">
                                <i class="fas fa-book text-5xl sm:text-6xl mb-4" aria-hidden="true"></i>
                                <div class="text-lg sm:text-xl"><?php echo htmlspecialchars($book['title']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="md:w-2/3 flex-1">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-3 sm:mb-4" itemprop="name"><?php echo htmlspecialchars($book['title']); ?></h1>
                    <div class="flex flex-wrap items-center mb-3 sm:mb-4">
                        <i class="fas fa-user text-gray-500 mr-2 text-sm sm:text-base" aria-hidden="true"></i>
                        <span class="text-base sm:text-lg text-gray-600" itemprop="author" itemscope itemtype="https://schema.org/Person">
                            <span itemprop="name"><?php echo \App\Helper\StringHelper::formatAuthors($book['author']); ?></span>
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-2 gap-2 sm:gap-4 mb-4 sm:mb-6">
                        <div class="bg-blue-50 p-2.5 sm:p-3 rounded-lg">
                            <div class="text-xs sm:text-sm text-gray-500 mb-1">文件类型</div>
                            <div class="font-semibold text-sm sm:text-base"><?php echo htmlspecialchars($book['file_format']); ?></div>
                        </div>
                        <div class="bg-green-50 p-2.5 sm:p-3 rounded-lg">
                            <div class="text-xs sm:text-sm text-gray-500 mb-1">文件大小</div>
                            <div class="font-semibold text-sm sm:text-base"><?php echo \App\Helper\StringHelper::formatFileSize($book['file_size']); ?></div>
                        </div>
                        <div class="bg-yellow-50 p-2.5 sm:p-3 rounded-lg">
                            <div class="text-xs sm:text-sm text-gray-500 mb-1">内容语言</div>
                            <div class="font-semibold text-sm sm:text-base"><?php echo htmlspecialchars($book['languages'] ?: "简体中文"); ?></div>
                        </div>
                        <div class="bg-purple-50 p-2.5 sm:p-3 rounded-lg">
                            <div class="text-xs sm:text-sm text-gray-500 mb-1">更新时间</div>
                            <div class="font-semibold text-sm sm:text-base"><?php echo substr($book['updated_at'], 0, 10); ?></div>
                        </div>
                    </div>

                    <div class="mb-4 sm:mb-6">
                        <h2 class="text-lg sm:text-xl font-semibold text-gray-800 mb-2">简介</h2>
                        <p class="text-sm sm:text-base text-gray-600 leading-relaxed" itemprop="description"><?php echo nl2br(htmlspecialchars($book['description'] ?? "暂无资料")); ?></p>
                    </div>

                    <div class="mb-4 sm:mb-6">
                        <h2 class="text-lg sm:text-xl font-semibold text-gray-800 mb-2">标签</h2>
                        <div class="flex flex-wrap gap-2">
                            <?php if ($book['tags']): ?>
                                <?php foreach (explode(',', $book['tags']) as $tag): ?>
                                    <a href="?q=<?php echo urlencode(trim($tag)); ?>&type=all" class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-2.5 sm:px-3 py-1 rounded-full text-xs sm:text-sm transition-colors">
                                        <?php echo htmlspecialchars(trim($tag)); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-sm sm:text-base text-gray-500">暂无标签</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 点赞、收藏、分享按钮 -->
                    <div class="flex flex-wrap items-center gap-3 sm:gap-4 mb-4 sm:mb-6">
                        <!-- 点赞按钮 -->
                        <?php if ($auth->isLoggedIn()): ?>
                            <button 
                                id="likeBtn" 
                                class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 sm:py-3 rounded-lg font-semibold transition-colors min-h-[44px] focus:outline-none focus:ring-2 focus:ring-offset-2 <?php echo $book['is_liked'] ? 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500' : 'bg-gray-200 hover:bg-gray-300 text-gray-700 focus:ring-gray-500'; ?>"
                                onclick="toggleLike(<?php echo $book['id']; ?>)"
                                aria-label="点赞">
                                <i class="fas fa-heart mr-2" aria-hidden="true"></i>
                                <span>点赞</span>
                                <span id="likeCount" class="ml-2"><?php echo $book['like_count']; ?></span>
                            </button>
                            
                            <!-- 收藏按钮 -->
                            <button 
                                id="favoriteBtn" 
                                class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 sm:py-3 rounded-lg font-semibold transition-colors min-h-[44px] focus:outline-none focus:ring-2 focus:ring-offset-2 <?php echo $book['is_favorited'] ? 'bg-yellow-600 hover:bg-yellow-700 text-white focus:ring-yellow-500' : 'bg-gray-200 hover:bg-gray-300 text-gray-700 focus:ring-gray-500'; ?>"
                                onclick="toggleFavorite(<?php echo $book['id']; ?>)"
                                aria-label="收藏">
                                <i class="fas fa-star mr-2" aria-hidden="true"></i>
                                <span>收藏</span>
                                <span id="favoriteCount" class="ml-2"><?php echo $book['favorite_count']; ?></span>
                            </button>
                        <?php else: ?>
                            <a href="?action=login" 
                               class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 sm:py-3 rounded-lg font-semibold transition-colors min-h-[44px] bg-gray-200 hover:bg-gray-300 text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                               aria-label="登录后点赞">
                                <i class="fas fa-heart mr-2" aria-hidden="true"></i>
                                <span>点赞</span>
                                <span class="ml-2"><?php echo $book['like_count']; ?></span>
                            </a>
                            <a href="?action=login" 
                               class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 sm:py-3 rounded-lg font-semibold transition-colors min-h-[44px] bg-gray-200 hover:bg-gray-300 text-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                               aria-label="登录后收藏">
                                <i class="fas fa-star mr-2" aria-hidden="true"></i>
                                <span>收藏</span>
                                <span class="ml-2"><?php echo $book['favorite_count']; ?></span>
                            </a>
                        <?php endif; ?>
                        
                        <!-- 分享按钮 -->
                        <button 
                            onclick="shareBook(<?php echo $book['id']; ?>, '<?php echo htmlspecialchars($book['title'], ENT_QUOTES); ?>')" 
                            class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 sm:py-3 rounded-lg font-semibold transition-colors min-h-[44px] bg-blue-600 hover:bg-blue-700 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            aria-label="分享">
                            <i class="fas fa-share-alt mr-2" aria-hidden="true"></i>
                            <span>分享</span>
                        </button>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                        <?php if ($auth->isLoggedIn()): ?>
                            <?php 
                            $downloadService = new DownloadService();
                            $networkHelper = new NetworkHelper();
                            $ipAddress = $networkHelper->getClientIp();
                            if ($downloadService->checkDownloadLimit($auth->getUserId(), $ipAddress)): 
                            ?>
                                <a href="?action=download&id=<?php echo $book['id']; ?>" 
                                   class="inline-flex items-center justify-center bg-green-600 hover:bg-green-700 active:bg-green-800 text-white px-5 sm:px-6 py-3 rounded-lg font-semibold transition-colors min-h-[44px] focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2" 
                                   aria-label="下载电子书">
                                    <i class="fas fa-download mr-2" aria-hidden="true"></i> 
                                    <span>下载电子书</span>
                                </a>
                            <?php else: ?>
                                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-3 sm:px-4 py-2.5 sm:py-3 rounded-lg text-sm sm:text-base">
                                    <i class="fas fa-exclamation-circle mr-2" aria-hidden="true"></i> 
                                    今日下载次数已达上限（5次），请明天再试
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="?action=login" 
                               class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white px-5 sm:px-6 py-3 rounded-lg font-semibold transition-colors min-h-[44px] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" 
                               aria-label="登录后下载">
                                <i class="fas fa-download mr-2" aria-hidden="true"></i> 
                                <span>登录后下载</span>
                            </a>
                        <?php endif; ?>

                        <a href="?" 
                           class="inline-flex items-center justify-center bg-gray-600 hover:bg-gray-700 active:bg-gray-800 text-white px-5 sm:px-6 py-3 rounded-lg font-semibold transition-colors min-h-[44px] focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2" 
                           aria-label="返回搜索">
                            <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i> 
                            <span>返回搜索</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </article>

    <div class="bg-white rounded-lg shadow-md p-3 sm:p-4 mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">相关推荐</h2>
            <!-- 布局切换按钮 -->
            <div class="flex items-center gap-1 sm:gap-2 bg-gray-100 rounded-lg p-0.5 sm:p-1" role="group" aria-label="切换布局">
                <button 
                    onclick="switchBookLayout('grid', 'relatedBooksList')" 
                    id="relatedGridBtn" 
                    class="px-2 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition min-h-[36px] sm:min-h-[40px] focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $book_layout === 'grid' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-200'; ?>" 
                    aria-label="网格布局"
                    aria-pressed="<?php echo $book_layout === 'grid' ? 'true' : 'false'; ?>">
                    <i class="fas fa-th mr-1" aria-hidden="true"></i><span class="hidden xs:inline">方格</span>
                </button>
                <button 
                    onclick="switchBookLayout('list', 'relatedBooksList')" 
                    id="relatedListBtn" 
                    class="px-2 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition min-h-[36px] sm:min-h-[40px] focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $book_layout === 'list' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-200'; ?>" 
                    aria-label="列表布局"
                    aria-pressed="<?php echo $book_layout === 'list' ? 'true' : 'false'; ?>">
                    <i class="fas fa-list mr-1" aria-hidden="true"></i><span class="hidden xs:inline">列表</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 书籍列表 -->
    <?php echo ViewHelper::renderBooksList($related_books, $book_layout, $query ?? '', 'relatedBooksList'); ?>
</div>

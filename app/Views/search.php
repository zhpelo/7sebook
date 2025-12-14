<!-- 搜索结果页 -->
<div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-4 sm:py-6 lg:py-8">
    <!-- 搜索区域 -->
    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-4 sm:mb-6">
        <form method="GET" class="space-y-3 sm:space-y-4" role="search" aria-label="搜索电子书">
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                <div class="flex-grow">
                    <label for="search-input" class="sr-only">搜索关键词</label>
                    <input
                        type="search"
                        id="search-input"
                        name="q"
                        placeholder="输入关键词搜索图书..."
                        value="<?php echo htmlspecialchars($query ?? ''); ?>"
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-base sm:text-lg"
                        autocomplete="off"
                        aria-label="搜索关键词">
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white px-4 sm:px-6 py-2.5 sm:py-3 rounded-lg font-semibold transition-colors flex items-center justify-center min-h-[44px] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" aria-label="执行搜索">
                    <i class="fas fa-search mr-2" aria-hidden="true"></i> 
                    <span>搜索</span>
                </button>
            </div>

            <div class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4">
                <div class="flex-1 min-w-[140px]">
                    <label for="search-type" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">搜索范围</label>
                    <select id="search-type" name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm sm:text-base focus:ring-2 focus:ring-blue-500 focus:border-transparent min-h-[44px]">
                        <option value="all" <?php echo ($type ?? 'all') === 'all' ? 'selected' : ''; ?>>全部字段</option>
                        <option value="title" <?php echo ($type ?? 'all') === 'title' ? 'selected' : ''; ?>>仅书名</option>
                        <option value="author" <?php echo ($type ?? 'all') === 'author' ? 'selected' : ''; ?>>仅作者</option>
                        <option value="description" <?php echo ($type ?? 'all') === 'description' ? 'selected' : ''; ?>>仅简介</option>
                    </select>
                </div>

                <!-- 文件类型筛选 -->
                <div class="flex-1 min-w-[140px]">
                    <label for="file-format" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">文件类型</label>
                    <select id="file-format" name="format" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm sm:text-base focus:ring-2 focus:ring-blue-500 focus:border-transparent min-h-[44px]">
                        <option value="">所有格式</option>
                        <?php 
                        $supported_exts = \App\Config\Config::getSupportedExts();
                        foreach ($supported_exts as $ext): ?>
                            <option value="<?php echo $ext; ?>" <?php echo (($format ?? '') === $ext) ? 'selected' : ''; ?>>
                                <?php echo strtoupper($ext); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>

        <div class="mt-3 sm:mt-4 p-2.5 sm:p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-xs sm:text-sm text-blue-800">
                <i class="fas fa-lightbulb mr-1.5" aria-hidden="true"></i> 
                <strong>搜索技巧:</strong> 支持多关键词搜索，用空格分隔，如搜索"计算机 系统"
            </p>
        </div>
    </div>

    <!-- 搜索结果 -->
    <div id="resultsContainer">
        <?php if (empty($books ?? [])): ?>
            <div class="text-center py-12 sm:py-20">
                <i class="fas fa-book text-5xl sm:text-6xl text-gray-300 mb-4" aria-hidden="true"></i>
                <h3 class="text-lg sm:text-xl font-semibold text-gray-600 mb-2">未找到相关书籍</h3>
                <p class="text-sm sm:text-base text-gray-500">请尝试使用其他关键词搜索</p>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md p-3 sm:p-4 mb-4 sm:mb-6">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-800"><?php echo empty($query) ? '随机推荐' : '搜索结果'; ?></h2>
                    <div class="flex items-center justify-between sm:justify-end gap-3">
                        <?php if (!empty($query)): ?>
                            <p class="text-sm sm:text-base text-gray-600 hidden sm:block">
                                找到 <span class="font-bold text-blue-600"><?php echo number_format($total ?? 0); ?></span> 条结果
                            </p>
                        <?php else: ?>
                            <p class="text-sm sm:text-base text-gray-600 hidden sm:block">
                                为您推荐 <span class="font-bold text-blue-600"><?php echo count($books ?? []); ?></span> 本优质图书
                            </p>
                        <?php endif; ?>
                        <!-- 布局切换按钮 -->
                        <div class="flex items-center gap-1 sm:gap-2 bg-gray-100 rounded-lg p-0.5 sm:p-1" role="group" aria-label="切换布局">
                            <button 
                                onclick="switchBookLayout('grid', 'searchBooksList')" 
                                id="gridBtn" 
                                class="px-2 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition min-h-[36px] sm:min-h-[40px] focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $book_layout === 'grid' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-200'; ?>" 
                                aria-label="网格布局"
                                aria-pressed="<?php echo $book_layout === 'grid' ? 'true' : 'false'; ?>">
                                <i class="fas fa-th mr-1" aria-hidden="true"></i><span class="hidden xs:inline">方格</span>
                            </button>
                            <button 
                                onclick="switchBookLayout('list', 'searchBooksList')" 
                                id="listBtn" 
                                class="px-2 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition min-h-[36px] sm:min-h-[40px] focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo $book_layout === 'list' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-200'; ?>" 
                                aria-label="列表布局"
                                aria-pressed="<?php echo $book_layout === 'list' ? 'true' : 'false'; ?>">
                                <i class="fas fa-list mr-1" aria-hidden="true"></i><span class="hidden xs:inline">列表</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 书籍列表 -->
            <?php echo \App\Helper\ViewHelper::renderBooksList($books, $book_layout, $query ?? '', 'searchBooksList'); ?>

            <!-- 分页 -->
            <?php if (!empty($query) && ($total ?? 0) > $limit): ?>
                <?php
                $base_params = array_filter([
                    'q' => $query ?? '',
                    'type' => $type ?? 'all',
                    'format' => $format ?? ''
                ], function ($value) {
                    return !empty($value) || $value === 0;
                });
                echo \App\Helper\ViewHelper::renderPagination($page, $total_pages, $total, $limit, [
                    'base_params' => $base_params,
                    'preserve_params' => []
                ], [
                    'show_info' => false,
                    'show_jump' => true,
                    'max_buttons' => 5,
                    'show_first_last' => true,
                    'show_prev_next' => true
                ]);
                ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

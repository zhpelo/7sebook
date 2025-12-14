<?php

declare(strict_types=1);

namespace App\Helper;

use App\Auth\Auth;

/**
 * 视图渲染辅助类
 * 
 * 处理HTML生成相关的视图渲染逻辑，包括分页、书籍列表等
 */
class ViewHelper
{
    /**
     * 渲染分页组件
     * 
     * @param int $currentPage 当前页码
     * @param int $totalPages 总页数
     * @param int $totalRecords 总记录数
     * @param int $limit 每页记录数
     * @param callable|array|null $urlBuilder URL构建函数或配置数组
     * @param array<string, mixed> $options 配置选项
     * @return string HTML代码
     */
    public static function renderPagination(
        int $currentPage,
        int $totalPages,
        int $totalRecords = 0,
        int $limit = 20,
        $urlBuilder = null,
        array $options = []
    ): string {
        // 默认配置
        $defaults = [
            'show_info' => true,
            'show_jump' => true,
            'max_buttons' => 5,
            'show_first_last' => true,
            'show_prev_next' => true,
            'class_prefix' => ''
        ];
        $options = array_merge($defaults, $options);

        // 如果只有一页或总页数为0，不显示分页
        if ($totalPages <= 1) {
            if ($options['show_info'] && $totalRecords > 0) {
                return '<div class="text-sm text-gray-700 text-center py-4">共 ' . number_format($totalRecords) . ' 条记录</div>';
            }
            return '';
        }

        // 确定URL构建方式
        if ($urlBuilder === null) {
            // 默认使用统一的分页URL构建函数
            $urlBuilder = function ($page) {
                return UrlHelper::buildPageUrl($page);
            };
        } elseif (is_array($urlBuilder)) {
            // 如果是数组，使用统一的分页URL构建函数
            $baseParams = $urlBuilder['base_params'] ?? [];
            $preserveParams = $urlBuilder['preserve_params'] ?? [];
            $urlBuilder = function ($page) use ($baseParams, $preserveParams) {
                return UrlHelper::buildPageUrl($page, $baseParams, $preserveParams);
            };
        }

        // 计算显示的页码范围
        $half = floor($options['max_buttons'] / 2);
        $startPage = max(1, $currentPage - $half);
        $endPage = min($totalPages, $startPage + $options['max_buttons'] - 1);

        // 如果右侧空间不足，向左调整
        if ($endPage - $startPage < $options['max_buttons'] - 1) {
            $startPage = max(1, $endPage - $options['max_buttons'] + 1);
        }

        ob_start();
?>
    <div class="pagination-container mt-8 <?php echo htmlspecialchars($options['class_prefix']); ?>">
        <?php if ($options['show_info']): ?>
            <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-2">
                <div class="text-sm text-gray-700">
                    共 <span class="font-bold text-blue-600"><?php echo number_format($totalRecords); ?></span> 条记录，
                    第 <span class="font-bold"><?php echo $currentPage; ?></span> / <span class="font-bold"><?php echo $totalPages; ?></span> 页
                </div>
            </div>
        <?php endif; ?>

        <div class="flex flex-wrap justify-center items-center gap-2">
            <?php if ($options['show_prev_next']): ?>
                <!-- 上一页按钮 -->
                <?php if ($currentPage > 1): ?>
                    <a href="<?php echo htmlspecialchars($urlBuilder($currentPage - 1)); ?>"
                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-chevron-left mr-1"></i> 上一页
                    </a>
                <?php else: ?>
                    <span class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed">
                        <i class="fas fa-chevron-left mr-1"></i> 上一页
                    </span>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($options['show_first_last'] && $startPage > 1): ?>
                <!-- 首页按钮 -->
                <a href="<?php echo htmlspecialchars($urlBuilder(1)); ?>"
                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    1
                </a>
                <?php if ($startPage > 2): ?>
                    <span class="px-2 text-gray-500">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <!-- 页码按钮 -->
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i == $currentPage): ?>
                    <span class="px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-lg">
                        <?php echo $i; ?>
                    </span>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($urlBuilder($i)); ?>"
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($options['show_first_last'] && $endPage < $totalPages): ?>
                <!-- 末页按钮 -->
                <?php if ($endPage < $totalPages - 1): ?>
                    <span class="px-2 text-gray-500">...</span>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars($urlBuilder($totalPages)); ?>"
                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <?php echo $totalPages; ?>
                </a>
            <?php endif; ?>

            <?php if ($options['show_prev_next']): ?>
                <!-- 下一页按钮 -->
                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?php echo htmlspecialchars($urlBuilder($currentPage + 1)); ?>"
                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        下一页 <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                <?php else: ?>
                    <span class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed">
                        下一页 <i class="fas fa-chevron-right ml-1"></i>
                    </span>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($options['show_jump'] && $totalPages > $options['max_buttons']): ?>
                <!-- 跳转输入框 -->
                <?php
                $jumpId = 'pagination-jump-' . md5($urlBuilder(1) . $currentPage);
                $jumpUrlTemplate = $urlBuilder(1);
                ?>
                <div class="flex items-center gap-2 ml-2">
                    <span class="text-sm text-gray-600">跳至</span>
                    <input type="number"
                        id="<?php echo $jumpId; ?>"
                        min="1"
                        max="<?php echo $totalPages; ?>"
                        value="<?php echo $currentPage; ?>"
                        class="w-16 px-2 py-1 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button type="button"
                        onclick="paginationJump('<?php echo $jumpId; ?>', <?php echo $totalPages; ?>, '<?php echo htmlspecialchars($jumpUrlTemplate, ENT_QUOTES); ?>')"
                        class="px-3 py-1 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                        确定
                    </button>
                </div>
                <script>
                    (function() {
                        if (typeof window.paginationJump === 'undefined') {
                            window.paginationJump = function(inputId, maxPage, urlTemplate) {
                                const input = document.getElementById(inputId);
                                if (!input) return;
                                const page = parseInt(input.value);
                                if (page >= 1 && page <= maxPage) {
                                    // 替换URL中的页码，如果URL中没有page参数则添加
                                    let url = urlTemplate;
                                    if (url.includes('page=')) {
                                        url = url.replace(/page=\d+/, 'page=' + page);
                                    } else {
                                        url += (url.includes('?') ? '&' : '?') + 'page=' + page;
                                    }
                                    window.location.href = url;
                                } else {
                                    alert('请输入 1 到 ' + maxPage + ' 之间的页码');
                                    input.focus();
                                }
                            };
                        }
                        // 支持回车键跳转
                        const input = document.getElementById('<?php echo $jumpId; ?>');
                        if (input) {
                            input.addEventListener('keypress', function(e) {
                                if (e.key === 'Enter') {
                                    window.paginationJump('<?php echo $jumpId; ?>', <?php echo $totalPages; ?>, '<?php echo htmlspecialchars($jumpUrlTemplate, ENT_QUOTES); ?>');
                                }
                            });
                        }
                    })();
                </script>
            <?php endif; ?>
        </div>
    </div>
<?php
        return ob_get_clean();
    }

    /**
     * 渲染书籍列表（支持方格和列表两种布局）
     * 
     * @param array<int, array<string, mixed>> $books 书籍数组
     * @param string $layout 布局类型：'grid' 或 'list'，默认为 'grid'
     * @param string $query 搜索关键词（用于高亮显示）
     * @param string $containerId 容器ID（用于布局切换）
     * @return string HTML代码
     */
    public static function renderBooksList(
        array $books,
        string $layout = 'grid',
        string $query = '',
        string $containerId = 'booksList'
    ): string {
        if (empty($books)) {
            return '';
        }

        $layoutClass = $layout === 'list' ? 'list' : 'grid';
        $gridClass = $layout === 'list' ? 'grid-cols-1' : 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5';

        // 需要导入StringHelper的方法（在函数内部使用，避免循环依赖）
        $highlightMatches = function(string $text, string $query): string {
            return \App\Helper\StringHelper::highlightMatches($text, $query);
        };
        
        $formatFileSize = function($size): string {
            return \App\Helper\StringHelper::formatFileSize((int)$size);
        };

        ob_start();
?>
    <div id="<?php echo htmlspecialchars($containerId); ?>" class="books-container" data-layout="<?php echo htmlspecialchars($layout); ?>">
        <?php if ($layout === 'list'): ?>
            <!-- 列表布局 -->
            <div class="space-y-4">
                <?php foreach ($books as $book): ?>
                    <a href="?action=detail&id=<?php echo $book['id']; ?>">
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 flex mb-6">
                            <div class="w-32 sm:w-40 flex-shrink-0">
                                <div class="bg-gray-100 flex items-center justify-center h-full">
                                    <?php if ($book['cover_url']): ?>
                                        <img src="<?php echo htmlspecialchars($book['cover_url']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="text-gray-400 text-center p-2">
                                            <?php
                                            // 简单文件格式到FontAwesome图标类的映射
                                            $formatIconMap = [
                                                'pdf' => 'fa-file-pdf',
                                                'epub' => 'fa-file-lines',
                                                'mobi' => 'fa-file-lines',
                                                'azw3' => 'fa-file-lines',
                                                'txt' => 'fa-file-alt',
                                                'doc' => 'fa-file-word',
                                                'docx' => 'fa-file-word',
                                                'xls' => 'fa-file-excel',
                                                'xlsx' => 'fa-file-excel',
                                                'ppt' => 'fa-file-powerpoint',
                                                'pptx' => 'fa-file-powerpoint',
                                                'zip' => 'fa-file-archive',
                                                'rar' => 'fa-file-archive',
                                            ];
                                            $icon = 'fa-book';
                                            $fmt = strtolower($book['file_format'] ?? '');
                                            if ($fmt && isset($formatIconMap[$fmt])) {
                                                $icon = $formatIconMap[$fmt];
                                            }
                                            ?>
                                            <i class="fas <?php echo $icon; ?> text-2xl mb-1"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex-1 p-4 flex flex-col justify-between">
                                <div>
                                    <h3 class="font-bold text-lg mb-2"><?php echo empty($query) ? htmlspecialchars($book['title']) : $highlightMatches(htmlspecialchars($book['title']), $query); ?></h3>
                                    <?php if ($book['author']): ?>
                                        <p class="text-gray-600 mb-2">
                                            <i class="fas fa-user text-sm mr-2"></i>
                                            <?php echo empty($query) ? htmlspecialchars($book['author']) : $highlightMatches(htmlspecialchars($book['author']), $query); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($book['description'])): ?>
                                        <p class="text-gray-500 text-sm mb-2 line-clamp-4"><?php echo htmlspecialchars($book['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex gap-2 items-center text-xs">
                                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded"><?php echo htmlspecialchars($book['file_format']); ?></span>
                                        <span class="text-gray-500"><?php echo $formatFileSize($book['file_size']); ?></span>
                                    </div>
                                    <?php if (isset($book['relevance_score']) && !empty($query)): ?>
                                        <div class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                            相关度: <?php echo round($book['relevance_score'], 1); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- 方格布局 -->
            <div class="grid <?php echo $gridClass; ?> gap-4">
                <?php foreach ($books as $book): ?>
                    <a href="?action=detail&id=<?php echo $book['id']; ?>">
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                            <div class="bg-gray-100 flex items-center justify-center aspect-[2/3]">
                                <?php if ($book['cover_url']): ?>
                                    <img src="<?php echo htmlspecialchars($book['cover_url']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="text-gray-400 text-center p-4">
                                        <i class="fas fa-book text-4xl mb-2"></i>
                                        <div class="text-sm"><?php echo htmlspecialchars($book['title']); ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold text-lg mb-2 line-clamp-2"><?php echo empty($query) ? htmlspecialchars($book['title']) : $highlightMatches(htmlspecialchars($book['title']), $query); ?></h3>
                                <?php if (!empty($book['description'])): ?>
                                    <p class="text-gray-500 text-sm line-clamp-2 mb-2"><?php echo htmlspecialchars(substr($book['description'], 0, 150)); ?><?php echo strlen($book['description']) > 150 ? '...' : ''; ?></p>
                                <?php endif; ?>

                                <?php if ($book['author']): ?>
                                    <p class="text-gray-600 mb-2 line-clamp-2">
                                        <i class="fas fa-user text-sm mr-2"></i>
                                        <?php echo empty($query) ? htmlspecialchars($book['author']) : $highlightMatches(htmlspecialchars($book['author']), $query); ?>
                                    </p>
                                <?php endif; ?>
                                <div class="mt-2 flex justify-between items-center text-xs">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded"><?php echo htmlspecialchars($book['file_format']); ?></span>
                                    <span class="text-gray-500"><?php echo $formatFileSize($book['file_size']); ?></span>
                                </div>
                                <?php if (isset($book['relevance_score']) && !empty($query)): ?>
                                    <div class="mt-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded inline-block">
                                        相关度: <?php echo round($book['relevance_score'], 1); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php
        return ob_get_clean();
    }

    /**
     * 检查是否需要登录查看更多结果
     * 
     * @param int $total 总记录数
     * @param int $page 当前页码
     * @param int $limit 每页记录数
     * @return bool
     */
    public static function needsLoginForMoreResults(int $total, int $page, int $limit): bool
    {
        $totalPages = (int)ceil($total / $limit);
        $auth = new Auth();
        return !$auth->isLoggedIn() && $totalPages > 1 && $page === 1;
    }
}

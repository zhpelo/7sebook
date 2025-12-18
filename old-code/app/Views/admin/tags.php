<!-- 标签管理 -->
<div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">标签管理</h2>
            <p class="text-sm text-gray-500 mt-1">管理和组织书籍标签</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="?action=add_tag" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                <i class="fas fa-plus mr-2"></i> 添加标签
            </a>
            <a href="?action=merge_tags" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors flex items-center">
                <i class="fas fa-compress mr-2"></i> 合并标签
            </a>
        </div>
    </div>

    <!-- 搜索框和统计信息 -->
    <div class="mb-6">
        <form method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 mb-4">
            <input type="hidden" name="action" value="tags">
            <input type="text"
                name="search"
                value="<?php echo htmlspecialchars($tag_search ?? ''); ?>"
                placeholder="搜索标签名称..."
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
                <i class="fas fa-search mr-2"></i> 搜索
            </button>
            <?php if (!empty($tag_search)): ?>
                <a href="?action=tags" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors flex items-center justify-center">
                    <i class="fas fa-times mr-2"></i> 清除
                </a>
            <?php endif; ?>
        </form>
        
        <div class="flex items-center justify-between text-sm text-gray-600">
            <span>
                共找到 <strong class="text-gray-800"><?php echo $total_tags ?? 0; ?></strong> 个标签
                <?php if (!empty($tag_search)): ?>
                    （搜索：<strong><?php echo htmlspecialchars($tag_search); ?></strong>）
                <?php endif; ?>
            </span>
        </div>
    </div>

    <!-- 标签卡片网格 -->
    <?php if (empty($tags)): ?>
        <div class="text-center py-12 text-gray-500">
            <i class="fas fa-tags text-4xl mb-4 text-gray-300"></i>
            <p class="text-lg">
                <?php if (!empty($tag_search)): ?>
                    未找到匹配的标签
                <?php else: ?>
                    暂无标签
                <?php endif; ?>
            </p>
            <?php if (empty($tag_search)): ?>
                <a href="?action=add_tag" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i> 添加第一个标签
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($tags as $tag): ?>
                <?php
                $book_titles = explode(' | ', $tag['book_titles'] ?? '');
                $preview_count = 2;
                $preview_titles = array_slice($book_titles, 0, $preview_count);
                ?>
                <div class="border border-gray-200 rounded-lg p-5 hover:shadow-lg transition-shadow bg-white">
                    <!-- 标签头部 -->
                    <div class="flex items-start justify-between mb-3">
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-sm">
                            <i class="fas fa-tag mr-1.5"></i>
                            <?php echo htmlspecialchars($tag['tag_name']); ?>
                        </span>
                        <div class="flex items-center gap-1">
                            <a href="?action=edit_tag&tag=<?php echo urlencode($tag['tag_name']); ?>" 
                                class="text-green-600 hover:text-green-700 p-1.5 rounded hover:bg-green-50 transition-colors"
                                title="编辑标签">
                                <i class="fas fa-edit text-sm"></i>
                            </a>
                            <a href="?action=delete_tag&tag=<?php echo urlencode($tag['tag_name']); ?>" 
                                class="text-red-600 hover:text-red-700 p-1.5 rounded hover:bg-red-50 transition-colors" 
                                title="删除标签"
                                onclick="return confirm('确定要删除标签 \"<?php echo htmlspecialchars($tag['tag_name'], ENT_QUOTES); ?>\" 吗？\\n\\n这将删除该标签与所有书籍的关联关系！');">
                                <i class="fas fa-trash text-sm"></i>
                            </a>
                        </div>
                    </div>

                    <!-- 使用次数 -->
                    <div class="mb-3 flex items-center text-sm text-gray-600">
                        <i class="fas fa-book text-blue-500 mr-2"></i>
                        <span class="font-semibold text-gray-800"><?php echo $tag['usage_count']; ?></span>
                        <span class="ml-1">本关联书籍</span>
                    </div>

                    <!-- 关联书籍预览 -->
                    <div class="mb-4">
                        <?php if (!empty($preview_titles)): ?>
                            <div class="space-y-1.5">
                                <?php foreach ($preview_titles as $title): ?>
                                    <div class="text-sm text-gray-600 truncate">
                                        <i class="fas fa-circle text-blue-400 text-xs mr-2"></i>
                                        <?php echo htmlspecialchars(mb_substr($title, 0, 25)) . (mb_strlen($title) > 25 ? '...' : ''); ?>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($book_titles) > $preview_count): ?>
                                    <div class="text-xs text-gray-400 pt-1">
                                        还有 <?php echo count($book_titles) - $preview_count; ?> 本...
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-sm text-gray-400 italic">
                                <i class="fas fa-inbox mr-1"></i> 暂无关联书籍
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 操作按钮 -->
                    <div class="pt-3 border-t border-gray-100">
                        <a href="?action=view_tag_books&tag=<?php echo urlencode($tag['tag_name']); ?>" 
                            class="w-full block text-center px-3 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors text-sm font-medium">
                            <i class="fas fa-list mr-1"></i> 查看全部书籍
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- 分页 -->
    <?php if ($total_pages > 1): ?>
        <?php
        $base_params = ['action' => 'tags'];
        $additional_params = [];
        if (!empty($tag_search)) {
            $additional_params['search'] = $tag_search;
        }
        echo \App\Helper\ViewHelper::renderPagination($page, $total_pages, $total_tags, $limit, [
            'base_params' => array_merge($base_params, $additional_params),
            'preserve_params' => []
        ]);
        ?>
    <?php endif; ?>
</div>

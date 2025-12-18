<!-- 搜索记录 -->
<div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <h2 class="text-2xl font-bold text-gray-800">搜索记录</h2>
        <div class="text-sm text-gray-600">
            共 <?php echo number_format($total_search_records); ?> 条记录
        </div>
    </div>

    <!-- 搜索框 -->
    <div class="mb-4">
        <form method="GET" class="flex gap-2">
            <input type="hidden" name="action" value="search_history">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search_keyword ?? ''); ?>" 
                placeholder="搜索关键词..." 
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-search"></i>
            </button>
            <?php if (!empty($search_keyword)): ?>
                <a href="?action=search_history" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                    <i class="fas fa-times"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- 热门搜索统计 -->
    <?php if (!empty($popular_searches)): ?>
        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">热门搜索关键词（Top 20）</h3>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($popular_searches as $popular): ?>
                    <a href="?action=search_history&search=<?php echo urlencode($popular['search_query']); ?>" 
                       class="inline-flex items-center px-3 py-1 bg-white border border-blue-200 rounded-full text-sm text-gray-700 hover:bg-blue-100 transition-colors">
                        <span class="font-medium"><?php echo htmlspecialchars($popular['search_query']); ?></span>
                        <span class="ml-2 text-xs text-gray-500"><?php echo $popular['search_count']; ?>次</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 搜索记录列表 -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">搜索关键词</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">搜索类型</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用户</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">IP地址</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">结果数</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">搜索时间</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($search_records)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">暂无搜索记录</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($search_records as $record): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $record['id']; ?></td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <a href="index.php?q=<?php echo urlencode($record['search_query']); ?>" 
                                   target="_blank"
                                   class="text-blue-600 hover:text-blue-800 hover:underline">
                                    <?php echo htmlspecialchars($record['search_query']); ?>
                                </a>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                <?php 
                                $type_map = ['all' => '全部', 'title' => '书名', 'author' => '作者', 'description' => '简介'];
                                echo $type_map[$record['search_type']] ?? $record['search_type'];
                                ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($record['username'] ?? '游客'); ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">
                                <?php echo htmlspecialchars($record['ip_address'] ?? '未知'); ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                <?php echo number_format($record['results_count']); ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('Y-m-d H:i:s', strtotime($record['search_time'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 分页 -->
    <?php if ($total_pages > 1): ?>
        <?php
        $base_params = ['action' => 'search_history'];
        if (!empty($search_keyword)) {
            $base_params['search'] = $search_keyword;
        }
        echo \App\Helper\ViewHelper::renderPagination($page, $total_pages, $total_search_records, $limit, [
            'base_params' => $base_params,
            'preserve_params' => []
        ]);
        ?>
    <?php endif; ?>
</div>

<!-- 下载记录 -->
<!-- 统计卡片和图表 -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- 最近28天下载趋势图 -->
    <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">最近28天下载趋势</h3>
        <div class="h-64">
            <canvas id="downloadTrendChart"></canvas>
        </div>
    </div>

    <!-- 累计下载最高的10本书 -->
    <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">累计下载排行榜（Top 10）</h3>
        <div class="space-y-3 max-h-64 overflow-y-auto">
            <?php if (empty($top_books)): ?>
                <p class="text-gray-500 text-center py-4">暂无数据</p>
            <?php else: ?>
                <?php foreach ($top_books as $index => $book): ?>
                    <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm
                            <?php 
                            if ($index < 3) {
                                echo $index === 0 ? 'bg-yellow-100 text-yellow-800' : ($index === 1 ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800');
                            } else {
                                echo 'bg-blue-100 text-blue-800';
                            }
                            ?>">
                            <?php echo $index + 1; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($book['title']); ?>">
                                <?php echo htmlspecialchars($book['title']); ?>
                            </div>
                            <div class="text-sm text-gray-500 truncate">
                                <?php echo htmlspecialchars($book['author'] ?? '未知作者'); ?>
                            </div>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <div class="text-lg font-bold text-blue-600"><?php echo number_format($book['download_count']); ?></div>
                            <div class="text-xs text-gray-500">次下载</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 下载记录列表 -->
<div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">下载记录</h2>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用户</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">电子书</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">下载时间</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($downloads)): ?>
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">暂无下载记录</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($downloads as $download): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $download['id']; ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($download['username'] ?? '游客'); ?></td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($download['title'] ?? '未知'); ?>">
                                    <?php echo htmlspecialchars($download['title'] ?? '未知'); ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo $download['download_time']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 分页 -->
    <?php if ($total_pages > 1): ?>
        <?php
        echo \App\Helper\ViewHelper::renderPagination($page, $total_pages, $total_downloads, $limit, [
            'base_params' => ['action' => 'downloads'],
            'preserve_params' => []
        ]);
        ?>
    <?php endif; ?>
</div>

<!-- 下载趋势图表脚本 -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const downloadCtx = document.getElementById('downloadTrendChart');
        if (downloadCtx) {
            new Chart(downloadCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($download_dates); ?>,
                    datasets: [{
                        label: '下载次数',
                        data: <?php echo json_encode(array_values($daily_download_counts)); ?>,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: 'rgb(59, 130, 246)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                stepSize: 1
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });
        }
    });
</script>

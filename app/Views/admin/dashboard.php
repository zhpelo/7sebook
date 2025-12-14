<!-- 仪表盘 -->
<div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">系统概览</h2>
        <a href="index.php" target="_blank" class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-external-link-alt mr-2"></i>
            <span>访问前台</span>
        </a>
    </div>

    <!-- 统计卡片 -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl border border-blue-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">总用户数</p>
                    <p class="text-3xl font-bold text-blue-600"><?php echo number_format($stats['total_users']); ?></p>
                </div>
                <div class="bg-blue-500 rounded-full p-3">
                    <i class="fas fa-users text-white text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-xl border border-green-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">电子书数量</p>
                    <p class="text-3xl font-bold text-green-600"><?php echo number_format($stats['total_books']); ?></p>
                </div>
                <div class="bg-green-500 rounded-full p-3">
                    <i class="fas fa-book text-white text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 p-6 rounded-xl border border-yellow-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">总下载次数</p>
                    <p class="text-3xl font-bold text-yellow-600"><?php echo number_format($stats['total_downloads']); ?></p>
                </div>
                <div class="bg-yellow-500 rounded-full p-3">
                    <i class="fas fa-download text-white text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-xl border border-purple-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">今日下载</p>
                    <p class="text-3xl font-bold text-purple-600"><?php echo number_format($stats['today_downloads']); ?></p>
                </div>
                <div class="bg-purple-500 rounded-full p-3">
                    <i class="fas fa-calendar-day text-white text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 图表区域 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white p-4 lg:p-6 rounded-xl border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">近7天用户增长趋势</h3>
            <div class="h-64">
                <canvas id="userGrowthChart"></canvas>
            </div>
        </div>
        <div class="bg-white p-4 lg:p-6 rounded-xl border border-gray-200 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">近7天电子书增长趋势</h3>
            <div class="h-64">
                <canvas id="bookGrowthChart"></canvas>
            </div>
        </div>
    </div>

    <!-- 系统信息 -->
    <div class="bg-gray-50 p-4 lg:p-6 rounded-xl border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">系统信息</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center">
                <i class="fas fa-code text-blue-500 mr-3"></i>
                <div>
                    <p class="text-sm text-gray-600">PHP版本</p>
                    <p class="font-semibold text-gray-800"><?php echo PHP_VERSION; ?></p>
                </div>
            </div>
            <div class="flex items-center">
                <i class="fas fa-database text-green-500 mr-3"></i>
                <div>
                    <p class="text-sm text-gray-600">数据库</p>
                    <p class="font-semibold text-gray-800">MySQL <?php 
                        $pdo = \App\Database\Connection::getInstance();
                        echo $pdo->getAttribute(PDO::ATTR_SERVER_VERSION); 
                    ?></p>
                </div>
            </div>
            <div class="flex items-center">
                <i class="fas fa-user-shield text-purple-500 mr-3"></i>
                <div>
                    <p class="text-sm text-gray-600">当前用户</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['username'] ?? '未知'); ?></p>
                </div>
            </div>
            <div class="flex items-center">
                <i class="fas fa-clock text-yellow-500 mr-3"></i>
                <div>
                    <p class="text-sm text-gray-600">当前时间</p>
                    <p class="font-semibold text-gray-800"><?php echo \App\Helper\TimeHelper::getCurrentTime(); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 用户增长趋势图
        const userCtx = document.getElementById('userGrowthChart');
        if (userCtx) {
            new Chart(userCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($dates); ?>,
                    datasets: [{
                        label: '新增用户数',
                        data: <?php echo json_encode(array_values($daily_user_counts)); ?>,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
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
                            }
                        }
                    }
                }
            });
        }

        // 电子书增长趋势图
        const bookCtx = document.getElementById('bookGrowthChart');
        if (bookCtx) {
            new Chart(bookCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($dates); ?>,
                    datasets: [{
                        label: '新增电子书数',
                        data: <?php echo json_encode(array_values($daily_book_counts)); ?>,
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
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
                            }
                        }
                    }
                }
            });
        }
    });
</script>

<?php
// 带详情页和下载功能的电子书搜索系统
session_start();
require 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die('数据库连接失败');
}

// 用户认证相关函数
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_user_role() {
    return $_SESSION['user_role'] ?? 'guest';
}

// 检查用户今日下载次数
function check_download_limit($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM download_logs 
        WHERE user_id = :user_id 
        AND DATE(download_time) = CURDATE()
    ");
    $stmt->execute(['user_id' => $user_id]);
    $download_count = $stmt->fetchColumn();
    
    return $download_count < 5;
}

// 记录下载日志
function log_download($user_id, $book_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO download_logs (user_id, book_id, download_time) 
        VALUES (:user_id, :book_id, NOW())
    ");
    $stmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);
}

// 登录处理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
            exit;
        } else {
            $login_error = '用户名或密码错误';
        }
    }
}

// 注册处理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = trim($_POST['reg_username'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $email = trim($_POST['reg_email'] ?? '');
    
    if ($username && $password && $email) {
        // 检查用户名是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $register_error = '用户名已存在';
        } else {
            // 插入新用户
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, created_at) VALUES (?, ?, ?, NOW())");
            if ($stmt->execute([$username, $password_hash, $email])) {
                $register_success = '注册成功，请登录';
            } else {
                $register_error = '注册失败';
            }
        }
    } else {
        $register_error = '请填写完整信息';
    }
}

// 登出处理
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query(array_filter($_GET, function($k) { return $k !== 'logout'; }, ARRAY_FILTER_USE_KEY)));
    exit;
}

// 忘记密码处理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'forgot_password') {
    $email = trim($_POST['email'] ?? '');
    
    if ($email) {
        // 检查邮箱是否存在
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // 生成重置令牌
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // 保存令牌到数据库
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) 
                                      ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)");
            $stmt->execute([$email, $token, $expires]);
            
            // 发送重置邮件
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?action=reset_password&token=" . $token;
            
            $subject = "七秒电子书 - 密码重置";
            $message = "您好！\n\n您收到这封邮件是因为您请求重置密码。\n\n请点击下面的链接重置您的密码：\n" . $reset_link . "\n\n如果您没有请求重置密码，请忽略这封邮件。\n\n此链接将在1小时后过期。";
            $headers = "From: no-reply@7sebook.com\r\n";
            $headers .= "Reply-To: no-reply@7sebook.com\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            // 使用 mail() 函数发送邮件，并检查返回值
            $mail_sent = mail($email, $subject, $message, $headers);
            
            if ($mail_sent) {
                $forgot_password_message = '密码重置链接已发送到您的邮箱，请检查。';
            } else {
                // 提供更具体的错误信息
                $forgot_password_error = '邮件发送失败，请检查您的邮箱地址是否正确，或稍后再试。';
            }
        } else {
            $forgot_password_message = '如果该邮箱存在于我们的系统中，您将收到密码重置链接。';
        }
    } else {
        $forgot_password_error = '请输入邮箱地址。';
    }
}

// 显示重置密码表单
if (isset($_GET['action']) && $_GET['action'] === 'reset_password') {
    $token = $_GET['token'] ?? '';
    if ($token) {
        // 验证令牌
        $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $reset_request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reset_request) {
            // 检查令牌是否过期
            if (strtotime($reset_request['expires_at']) > time()) {
                $reset_token = $token;
                $reset_email = $reset_request['email'];
            } else {
                $reset_password_error = '密码重置链接已过期，请重新申请。';
                // 删除过期的令牌
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->execute([$token]);
            }
        } else {
            $reset_password_error = '无效的密码重置链接。';
        }
    } else {
        $reset_password_error = '缺少必要的参数。';
    }
}

// 处理密码重置
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($token && $password && $confirm_password) {
        if ($password === $confirm_password) {
            // 验证令牌
            $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
            $reset_request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($reset_request) {
                // 检查令牌是否过期
                if (strtotime($reset_request['expires_at']) > time()) {
                    // 更新密码
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
                    $stmt->execute([$password_hash, $reset_request['email']]);
                    
                    // 删除已使用的令牌
                    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                    $stmt->execute([$token]);
                    
                    $reset_password_success = '密码重置成功，您可以使用新密码登录了。';
                } else {
                    $reset_password_error = '密码重置链接已过期，请重新申请。';
                }
            } else {
                $reset_password_error = '无效的密码重置链接。';
            }
        } else {
            $reset_password_error = '两次输入的密码不一致。';
        }
    } else {
        $reset_password_error = '请填写所有必填字段。';
    }
}

// 获取当前操作
$action = $_GET['action'] ?? 'search';
$book_id = $_GET['id'] ?? null;

// 获取电子书详情
$book = null;
if ($action === 'detail' && $book_id) {
    $stmt = $pdo->prepare("
        SELECT b.*, 
               DATE_FORMAT(b.publish_date, '%Y年%m月%d日') as formatted_date,
               GROUP_CONCAT(bt.tag_name) as tags
        FROM books b
        LEFT JOIN book_tags bt ON b.id = bt.book_id
        WHERE b.id = ?
        GROUP BY b.id
    ");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        $action = 'search';
    }
}

// 处理下载请求
if ($action === 'download' && $book_id && is_logged_in()) {
    $user_id = get_user_id();
    
    // 检查下载限制
    if (!check_download_limit($user_id)) {
        $download_error = '今日下载次数已达上限（5次），请明天再试';
    } else {
        // 记录下载日志
        log_download($user_id, $book_id);

        $stmt = $pdo->prepare(" SELECT file_path FROM books  WHERE id = ? ");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);


        $filepath = __DIR__.$book['file_path'];
        
        
        // 如果文件存在，提供下载
        if (file_exists($filepath)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        } else {
            // 如果文件不存在，返回错误
            $download_error = '文件不存在';
            die("文件不存在");
        }
    }
}

// 搜索相关代码
if ($action === 'search') {
    // 获取搜索参数
    $query = trim($_GET['q'] ?? '');
    $type = $_GET['type'] ?? 'all';
    $page = max(1, intval($_GET['page'] ?? 1));

    // 未登录用户只能查看第一页
    if (!is_logged_in() && $page > 1) {
        $page = 1;
    }

    $limit = 20;
    $offset = ($page - 1) * $limit;

    $books = [];
    $total = 0;
    $time_taken = 0;

    if (!empty($query)) {
        // 记录开始时间
        $start_time = microtime(true);

        // 构建搜索条件
        $search_conditions = build_search_conditions($type, $query);

        // 获取总数
        $count_sql = "SELECT COUNT(DISTINCT b.id) FROM books b WHERE {$search_conditions['where']}";
        $stmt = $pdo->prepare($count_sql);
        $stmt->execute($search_conditions['params']);
        $total = $stmt->fetchColumn();

        // 获取分页数据
        $sql = "SELECT DISTINCT b.id, b.title, b.author, b.description, b.cover_url, b.publish_date, b.category,
                ({$search_conditions['relevance']}) as relevance_score
                FROM books b 
                WHERE {$search_conditions['where']} 
                ORDER BY relevance_score DESC, b.id 
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        // 绑定关键词参数
        foreach ($search_conditions['params'] as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 计算耗时
        $end_time = microtime(true);
        $time_taken = round($end_time - $start_time, 3);
    } else {
        // 没有搜索关键词时，显示最新的100本书
        $start_time = microtime(true);
        $sql = "SELECT * FROM books ORDER by id Desc LIMIT 100 ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = count($books);
        $end_time = microtime(true);
        $time_taken = round($end_time - $start_time, 3);
    }
}

/**
 * 构建搜索条件
 */
function build_search_conditions($type, $query) {
    $where_parts = [];
    $relevance_parts = [];
    $params = [];
    
    // 将查询字符串按空格分割为关键词
    $keywords = array_filter(explode(' ', $query), function($k) { return strlen(trim($k)) > 0; });
    
    foreach ($keywords as $idx => $keyword) {
        $param_name = ':keyword' . $idx;
        $params[$param_name] = '%' . $keyword . '%';
        
        $field_conditions = [];
        $relevance_expressions = [];
        
        switch ($type) {
            case 'title':
                $field_conditions[] = "b.title LIKE :keyword{$idx}";
                $relevance_expressions[] = "CASE WHEN b.title LIKE :keyword{$idx} THEN 10 ELSE 0 END";
                break;
            case 'author':
                $field_conditions[] = "b.author LIKE :keyword{$idx}";
                $relevance_expressions[] = "CASE WHEN b.author LIKE :keyword{$idx} THEN 8 ELSE 0 END";
                break;
            case 'description':
                $field_conditions[] = "b.description LIKE :keyword{$idx}";
                $relevance_expressions[] = "CASE WHEN b.description LIKE :keyword{$idx} THEN 5 ELSE 0 END";
                break;
            default:
                $field_conditions[] = "(b.title LIKE :keyword{$idx} OR b.author LIKE :keyword{$idx} OR b.description LIKE :keyword{$idx})";
                $relevance_expressions[] = "
                    CASE 
                        WHEN b.title LIKE :keyword{$idx} THEN 10
                        WHEN b.author LIKE :keyword{$idx} THEN 8
                        WHEN b.description LIKE :keyword{$idx} THEN 5
                        ELSE 0
                    END";
        }
        
        $where_parts[] = '(' . implode(' OR ', $field_conditions) . ')';
        $relevance_parts[] = implode(' + ', $relevance_expressions);
    }
    
    $where_clause = implode(' AND ', $where_parts);
    $relevance_clause = implode(' + ', $relevance_parts);
    
    return [
        'where' => $where_clause,
        'relevance' => $relevance_clause,
        'params' => $params
    ];
}

/**
 * 高亮匹配文本
 */
function highlight_matches($text, $query) {
    if (!$query || !$text) return $text;
    
    $keywords = array_filter(explode(' ', $query), function($k) { return strlen($k) > 0; });
    $result = $text;
    
    foreach ($keywords as $keyword) {
        if (strlen($keyword) > 0) {
            // 转义正则表达式特殊字符
            $escaped_keyword = preg_quote($keyword, '/');
            $result = preg_replace('/('.preg_quote($keyword, '/').')/i', '<span class="bg-yellow-200">$1</span>', $result);
        }
    }
    
    return $result;
}

// 构建分页URL
function build_page_url($query, $type, $new_page) {
    $params = ['q' => $query, 'type' => $type, 'page' => $new_page];
    return '?' . http_build_query($params);
}

// 检查是否需要登录查看更多结果
function needs_login_for_more_results($total, $page, $limit) {
    $total_pages = ceil($total / $limit);
    return !is_logged_in() && $total_pages > 1 && $page === 1;
}

// 获取用户今日下载次数
function get_user_download_count() {
    global $pdo;
    
    if (!is_logged_in()) return 0;
    
    $user_id = get_user_id();
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM download_logs 
        WHERE user_id = :user_id 
        AND DATE(download_time) = CURDATE()
    ");
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($action === 'detail' && $book): ?>
        <title><?php echo htmlspecialchars($book['title']); ?> - 电子书详情</title>
        <meta name="description" content="<?php echo htmlspecialchars($book['description']); ?>">
    <?php else: ?>
        <title><?php echo $query ? htmlspecialchars($query) . ' - 电子书搜索结果' : '七秒电子书'; ?></title>
        <meta name="description" content="<?php echo $query ? '搜索"' . htmlspecialchars($query) . '"的电子书结果' : '中文电子书搜索引擎，支持书名、作者、简介搜索'; ?>">
    <?php endif; ?>
    <meta name="keywords" content="电子书,搜索,详情,下载,图书,PDF">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-S1VQ1GTE0R"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
    
      gtag('config', 'G-S1VQ1GTE0R');
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- 导航栏 -->
    <nav class="bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="./">
                    <div class="flex-shrink-0 flex items-center">
                        
                        <i class="fas fa-book text-2xl mr-2"></i>
                        <span class="text-xl font-bold">七秒电子书</span>
                        
                    </div>
                    </a>
                </div>
                <div class="flex items-center">
                    <div class="mr-4 text-sm">
                        <?php if (is_logged_in()): ?>
                            <span>欢迎, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                            <?php if (get_user_role() === 'user'): ?>
                                <span class="ml-2 text-xs bg-green-500 px-2 py-1 rounded">已下载: <?php echo get_user_download_count(); ?>/5</span>
                            <?php endif; ?>
                            <a href="?logout=1" class="ml-3 text-sm bg-red-500 hover:bg-red-600 px-3 py-1 rounded transition">退出</a>
                        <?php else: ?>
                            <span>游客</span>
                            <button onclick="openAuthModal('login')" class="ml-3 text-sm bg-green-500 hover:bg-green-600 px-3 py-1 rounded transition">登录</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <?php if ($action === 'detail' && $book): ?>
    <!-- 详情页 -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <div class="flex flex-col md:flex-row">
                    <div class="md:w-1/3 mb-6 md:mb-0 md:pr-6">
                        <div class="bg-gray-100 flex items-center justify-center rounded-lg">
                            <?php if ($book['cover_url']): ?>
                                <img src="<?php echo htmlspecialchars($book['cover_url']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="w-full h-full object-cover rounded-lg">
                            <?php else: ?>
                                <div class="text-gray-400 text-center p-4">
                                    <i class="fas fa-book text-6xl mb-4"></i>
                                    <div class="text-xl"><?php echo htmlspecialchars(mb_substr($book['title'], 0, 8)); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="md:w-2/3">
                        <h1 class="text-3xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($book['title']); ?></h1>
                        <div class="flex items-center mb-4">
                            <i class="fas fa-user text-gray-500 mr-2"></i>
                            <span class="text-lg text-gray-600"><?php echo htmlspecialchars($book['author']); ?></span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-blue-50 p-3 rounded-lg">
                                <div class="text-sm text-gray-500">类别</div>
                                <div class="font-semibold"><?php echo htmlspecialchars($book['category'] ?? "" ); ?></div>
                            </div>
                            <div class="bg-green-50 p-3 rounded-lg">
                                <div class="text-sm text-gray-500">出版社</div>
                                <div class="font-semibold"><?php echo htmlspecialchars($book['publisher'] ?? "" ); ?></div>
                            </div>
                            <div class="bg-yellow-50 p-3 rounded-lg">
                                <div class="text-sm text-gray-500">出版日期</div>
                                <div class="font-semibold"><?php echo htmlspecialchars($book['formatted_date'] ?? ""); ?></div>
                            </div>
                            <div class="bg-purple-50 p-3 rounded-lg">
                                <div class="text-sm text-gray-500">页数</div>
                                <div class="font-semibold"><?php echo $book['page_count']; ?>页</div>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-2">简介</h2>
                            <p class="text-gray-600"><?php echo htmlspecialchars($book['description'] ?? ""); ?></p>
                        </div>
                        
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-2">标签</h2>
                            <div class="flex flex-wrap gap-2">
                                <?php if ($book['tags']): ?>
                                    <?php foreach (explode(',', $book['tags']) as $tag): ?>
                                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-gray-500">暂无标签</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-2">文件信息</h2>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <div class="text-sm text-gray-500">格式</div>
                                        <div class="font-semibold"><?php echo htmlspecialchars($book['file_format']); ?></div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">大小</div>
                                        <div class="font-semibold"><?php echo htmlspecialchars($book['file_size']); ?></div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">语言</div>
                                        <div class="font-semibold"><?php echo htmlspecialchars($book['languages']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (is_logged_in()): ?>
                            <?php if (check_download_limit(get_user_id())): ?>
                                <a href="?action=download&id=<?php echo $book['id']; ?>" class="inline-block bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                                    <i class="fas fa-download mr-2"></i> 下载电子书
                                </a>
                            <?php else: ?>
                                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg">
                                    <i class="fas fa-exclamation-circle mr-2"></i> 今日下载次数已达上限（5次），请明天再试
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <button onclick="openAuthModal('login')" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                                <i class="fas fa-download mr-2"></i> 登录后下载
                            </button>
                        <?php endif; ?>
                        
                        <a href="?" class="ml-4 inline-block bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                            <i class="fas fa-arrow-left mr-2"></i> 返回搜索
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- 搜索结果页 -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 搜索区域 -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <form method="GET" class="space-y-4">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-grow">
                        <input 
                            type="text" 
                            name="q" 
                            placeholder="输入关键词搜索图书..." 
                            value="<?php echo htmlspecialchars($query ?? ''); ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg"
                        >
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center justify-center">
                        <i class="fas fa-search mr-2"></i> 搜索
                    </button>
                </div>
                
                <div class="flex flex-wrap gap-4 items-center">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">搜索范围</label>
                        <select name="type" class="border border-gray-300 rounded-lg px-3 py-2">
                            <option value="all" <?php echo ($type ?? 'all') === 'all' ? 'selected' : ''; ?>>全部字段</option>
                            <option value="title" <?php echo ($type ?? 'all') === 'title' ? 'selected' : ''; ?>>仅书名</option>
                            <option value="author" <?php echo ($type ?? 'all') === 'author' ? 'selected' : ''; ?>>仅作者</option>
                            <option value="description" <?php echo ($type ?? 'all') === 'description' ? 'selected' : ''; ?>>仅简介</option>
                        </select>
                    </div>
                </div>
            </form>
            
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-800"><i class="fas fa-lightbulb mr-2"></i> <strong>搜索技巧:</strong> 支持多关键词搜索，用空格分隔，如搜索"计算机 系统"</p>
            </div>
        </div>

        <!-- 搜索统计 -->
        <?php if ($query): ?>
        <div class="bg-white rounded-lg shadow-md p-4 mb-6">
            <div class="flex flex-wrap justify-between items-center">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">搜索结果</h2>
                    <p class="text-gray-600">找到 <span class="font-bold text-blue-600"><?php echo $total ?? 0; ?></span> 条结果</p>
                </div>
                <div class="text-sm text-gray-500">
                    搜索耗时: <?php echo $time_taken ?? 0; ?>秒
                </div>
            </div>
        </div>

        <?php if (needs_login_for_more_results($total ?? 0, $page ?? 1, $limit)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        搜索结果较多，当前仅显示第一页。登录后可查看全部结果。
                        <button onclick="openAuthModal('login')" class="ml-2 text-blue-600 hover:text-blue-800 font-medium">
                            点击登录
                        </button>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- 搜索结果 -->
        <div id="resultsContainer">
            <?php // if (empty($query)): ?>
                <!-- <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">最新图书</h2>
                    <p class="text-gray-600">显示最新 <span class="font-bold text-blue-600">100</span> 本图书</p>
                </div> -->
            <?php if (empty($books ?? [])): ?>
                <div class="text-center py-20">
                    <i class="fas fa-book text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">未找到相关书籍</h3>
                    <p class="text-gray-500">请尝试使用其他关键词搜索</p>
                </div>
            <?php else: ?>
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800"><?php echo empty($query) ? '最新图书' : '搜索结果'; ?></h2>
                    <p class="text-gray-600">找到 <span class="font-bold text-blue-600"><?php echo $total ?? 0; ?></span> 条结果</p>
                </div>
                <!-- 书籍网格 -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 mb-8">
                    <?php foreach ($books as $book): ?>
                    <a href="?action=detail&id=<?php echo $book['id']; ?>">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                        <div class="bg-gray-100 flex items-center justify-center">
                            <?php if ($book['cover_url']): ?>
                                <img src="<?php echo htmlspecialchars($book['cover_url']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="text-gray-400 text-center p-4">
                                    <i class="fas fa-book text-4xl mb-2"></i>
                                    <div class="text-sm"><?php echo htmlspecialchars(mb_substr($book['title'], 0, 8)); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-lg mb-2 line-clamp-2"><?php echo empty($query) ? htmlspecialchars($book['title']) : highlight_matches(htmlspecialchars($book['title']), $query ?? ''); ?></h3>
                            <p class="text-gray-600 mb-2 flex items-center">
                                <i class="fas fa-user text-sm mr-2"></i>
                                <?php echo empty($query) ? htmlspecialchars($book['author']) : highlight_matches(htmlspecialchars($book['author']), $query ?? ''); ?>
                            </p>
                            <p class="text-gray-500 text-sm line-clamp-3"><?php echo empty($query) ? htmlspecialchars(mb_substr($book['description'] ?? "", 0, 100) . '...') : highlight_matches(htmlspecialchars(mb_substr($book['description'] ?? "", 0, 100) . '...'), $query ?? ''); ?></p>
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

                <!-- 分页 -->
                <?php if (!empty($query) && ($total ?? 0) > $limit): ?>
                <div class="flex flex-wrap justify-center items-center gap-2 mb-8">
                    <?php
                    $total_pages = ceil($total / $limit);
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    ?>
                    
                    <?php if ($page > 1): ?>
                        <a href="<?php echo build_page_url($query, $type, $page - 1); ?>" class="flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            <i class="fas fa-chevron-left mr-1"></i> 上一页
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if (is_logged_in() || $i == 1): ?>
                            <a href="<?php echo build_page_url($query, $type, $i); ?>" class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-blue-600 border border-blue-600' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-lg">
                                <?php echo $i; ?>
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-lg cursor-not-allowed">...</span>
                            <?php break; ?>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages && is_logged_in()): ?>
                        <a href="<?php echo build_page_url($query, $type, $page + 1); ?>" class="flex items-center px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            下一页 <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    <?php elseif ($page < $total_pages && !is_logged_in()): ?>
                        <span class="flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-lg cursor-not-allowed">
                            下一页 <i class="fas fa-chevron-right ml-1"></i>
                        </span>
                        <div class="mt-4 text-center w-full">
                            <div class="inline-flex items-center bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                <i class="fas fa-lock text-yellow-500 mr-2"></i>
                                <span class="text-yellow-700">需要登录查看更多结果</span>
                                <button onclick="openAuthModal('login')" class="ml-3 text-blue-600 hover:text-blue-800 font-medium">
                                    登录
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 添加重置密码表单 -->
    <?php if (isset($_GET['action']) && $_GET['action'] === 'reset_password'): ?>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">重置密码</h3>
                    <a href="./" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
                
                <?php if (isset($reset_password_error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($reset_password_error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($reset_password_success)): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($reset_password_success); ?>
                    </div>
                    <div class="text-center">
                        <a href="./" class="text-blue-600 hover:text-blue-800 font-medium">返回首页</a>
                    </div>
                <?php elseif (isset($reset_token) && isset($reset_email)): ?>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($reset_token); ?>">
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">邮箱</label>
                            <input type="email" value="<?php echo htmlspecialchars($reset_email); ?>" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="password">新密码</label>
                            <input type="password" name="password" id="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">确认新密码</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-semibold transition">
                            重置密码
                        </button>
                    </form>
                <?php else: ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                        无效的重置请求
                    </div>
                    <div class="text-center">
                        <a href="./" class="text-blue-600 hover:text-blue-800 font-medium">返回首页</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 登录/注册模态框 -->
    <div id="authModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold" id="authModalTitle">用户登录</h3>
                    <button onclick="closeAuthModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- 登录表单 -->
                <div id="loginForm" class="space-y-4">
                    <?php if (isset($login_error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                            <?php echo htmlspecialchars($login_error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="login">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="username">用户名</label>
                            <input type="text" name="username" id="username" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="password">密码</label>
                            <input type="password" name="password" id="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-semibold transition">
                            登录
                        </button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <button onclick="showForgotPasswordForm()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            忘记密码？
                        </button>
                    </div>
                    
                    <div class="text-center mt-4">
                        <p class="text-gray-600">还没有账号？</p>
                        <button onclick="switchToRegister()" class="text-blue-600 hover:text-blue-800 font-medium">
                            立即注册
                        </button>
                    </div>
                </div>
                
                <!-- 忘记密码表单 -->
                <div id="forgotPasswordForm" class="space-y-4 hidden">
                    <?php if (isset($forgot_password_error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                            <?php echo htmlspecialchars($forgot_password_error); ?>
                        </div>
                    <?php elseif (isset($forgot_password_message)): ?>
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                            <?php echo htmlspecialchars($forgot_password_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="forgot_password">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="forgot_email">邮箱地址</label>
                            <input type="email" name="email" id="forgot_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-semibold transition">
                            发送重置链接
                        </button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <button onclick="switchToLogin()" class="text-blue-600 hover:text-blue-800 font-medium">
                            返回登录
                        </button>
                    </div>
                </div>
                
                <!-- 注册表单 -->
                <div id="registerForm" class="space-y-4 hidden">
                    <?php if (isset($register_error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                            <?php echo htmlspecialchars($register_error); ?>
                        </div>
                    <?php elseif (isset($register_success)): ?>
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                            <?php echo htmlspecialchars($register_success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="register">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="reg_username">用户名</label>
                            <input type="text" name="reg_username" id="reg_username" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="reg_email">邮箱</label>
                            <input type="email" name="reg_email" id="reg_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="reg_password">密码</label>
                            <input type="password" name="reg_password" id="reg_password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg font-semibold transition">
                            注册
                        </button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="text-gray-600">已有账号？</p>
                        <button onclick="switchToLogin()" class="text-blue-600 hover:text-blue-800 font-medium">
                            立即登录
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 模态框控制
        function openAuthModal(type) {
            document.getElementById('authModal').classList.remove('hidden');
            
            if (type === 'register') {
                switchToRegister();
            } else {
                switchToLogin();
            }
        }
        
        function closeAuthModal() {
            document.getElementById('authModal').classList.add('hidden');
        }
        
        function switchToLogin() {
            document.getElementById('authModalTitle').textContent = '用户登录';
            document.getElementById('loginForm').classList.remove('hidden');
            document.getElementById('registerForm').classList.add('hidden');
            document.getElementById('forgotPasswordForm').classList.add('hidden');
        }
        
        function switchToRegister() {
            document.getElementById('authModalTitle').textContent = '用户注册';
            document.getElementById('registerForm').classList.remove('hidden');
            document.getElementById('loginForm').classList.add('hidden');
            document.getElementById('forgotPasswordForm').classList.add('hidden');
        }
        
        function showForgotPasswordForm() {
            document.getElementById('authModalTitle').textContent = '忘记密码';
            document.getElementById('loginForm').classList.add('hidden');
            document.getElementById('registerForm').classList.add('hidden');
            document.getElementById('forgotPasswordForm').classList.remove('hidden');
        }
        
        // 点击背景关闭模态框
        document.getElementById('authModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAuthModal();
            }
        });
        
        // ESC键关闭模态框
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAuthModal();
            }
        });
        
        // 如果URL中有错误参数，自动打开登录模态框
        <?php if (isset($login_error) || isset($register_error) || isset($register_success) || isset($forgot_password_error) || isset($forgot_password_message)): ?>
            window.onload = function() {
                openAuthModal('login');
                <?php if (isset($forgot_password_error) || isset($forgot_password_message)): ?>
                showForgotPasswordForm();
                <?php endif; ?>
            };
        <?php endif; ?>
    </script>
</body>
</html>

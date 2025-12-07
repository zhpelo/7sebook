<?php
// 网站前台页面
session_start();
require 'common.php';

// 登录处理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $user = verify_user_login($username, $password);

        if ($user) {
            set_user_session($user);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $login_error = '用户名或密码错误';
            $action = 'login'; // 保持显示登录页面
        }
    }
}

// 注册处理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $username = trim($_POST['reg_username'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $email = trim($_POST['reg_email'] ?? '');

    if ($username && $password && $email) {
        // 验证用户名格式
        $username_validation = validate_username($username);
        if (!$username_validation['valid']) {
            $register_error = $username_validation['error'];
            $action = 'register'; // 保持显示注册页面
        } else {
            // 验证密码格式
            $password_validation = validate_password($password);
            if (!$password_validation['valid']) {
                $register_error = $password_validation['error'];
                $action = 'register';
            } else {
                // 统一将邮箱转为小写
                $email = strtolower($email);
                
                // 验证邮箱格式
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $register_error = '请输入有效的邮箱地址';
                    $action = 'register';
                } else {
                    // 检查用户名是否已存在
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $register_error = '用户名已存在';
                        $action = 'register'; // 保持显示注册页面
                    } else {
                        // 检查邮箱是否已存在（使用小写比对）
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ?");
                        $stmt->execute([$email]);
                        if ($stmt->fetch()) {
                            $register_error = '该邮箱已被注册';
                            $action = 'register'; // 保持显示注册页面
                        } else {
                            // 插入新用户
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            $current_time = get_current_time();
                            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, created_at) VALUES (?, ?, ?, ?)");
                            if ($stmt->execute([$username, $password_hash, $email, $current_time])) {
                                // 注册成功后自动登录
                                $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE username = ?");
                                $stmt->execute([$username]);
                                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                                if ($user) {
                                    set_user_session($user);
                                    header('Location: ' . $_SERVER['PHP_SELF']);
                                    exit;
                                }

                                $register_success = '注册成功，请登录';
                                $action = 'login'; // 显示登录页面
                            } else {
                                $register_error = '注册失败';
                                $action = 'register'; // 保持显示注册页面
                            }
                        }
                    }
                }
            }
        }
    } else {
        $register_error = '请填写完整信息';
        $action = 'register'; // 保持显示注册页面
    }
}

// 登出处理
if (isset($_GET['logout'])) {
    clear_user_session();
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query(array_filter($_GET, function ($k) {
        return $k !== 'logout';
    }, ARRAY_FILTER_USE_KEY)));
    exit;
}

// 忘记密码处理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'forgot_password') {
    $email = trim($_POST['email'] ?? '');
    
    // 统一转为小写
    $email = strtolower($email);

    if ($email) {
        // 检查邮箱是否存在（使用小写比对）
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE LOWER(email) = ?");
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
    $action = 'forgot_password'; // 保持显示忘记密码页面
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
        // 验证密码格式
        $password_validation = validate_password($password);
        if (!$password_validation['valid']) {
            $reset_password_error = $password_validation['error'];
        } elseif ($password !== $confirm_password) {
            $reset_password_error = '两次输入的密码不一致。';
        } else {
            // 验证令牌
            $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
            $reset_request = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($reset_request) {
                // 检查令牌是否过期
                if (strtotime($reset_request['expires_at']) > time()) {
                    // 更新密码（使用小写邮箱）
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $reset_email_lower = strtolower($reset_request['email']);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE LOWER(email) = ?");
                    $stmt->execute([$password_hash, $reset_email_lower]);

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
        }
    } else {
        $reset_password_error = '请填写所有必填字段。';
    }
    $action = 'reset_password'; // 保持显示重置密码页面
}

// 获取当前操作（如果之前没有设置，则从GET获取）
if (!isset($action)) {
    $action = $_GET['action'] ?? 'search';
}
$book_id = $_GET['id'] ?? null;

// 初始化可能用到的变量，避免未定义警告
$query = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all';
$format = $_GET['format'] ?? '';
$books = [];
$total = 0;

// 获取用户布局偏好（从 Cookie 读取，默认为 grid）
$book_layout = $_COOKIE['book_layout'] ?? 'grid';

// 初始化变量
$popular_searches_list = [];
$download_ranking_books = [];

// 处理热门搜索页面
if ($action === 'popular_searches') {
    $popular_searches_list = get_popular_searches(100);
}

// 处理下载排行页面
if ($action === 'download_ranking') {
    $download_ranking_books = get_download_ranking(100);
}

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
    } else {
        // 获取相关推荐书籍
        $related_books = [];

        $stmt = $pdo->prepare("
            SELECT *
            FROM books
            WHERE id < ?
            ORDER BY id DESC
            LIMIT 10
        ");
        $stmt->execute([$book_id]);
        $related_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// 处理下载请求
if ($action === 'download' && $book_id && is_logged_in()) {
    $user_id = get_user_id();
    
    // 验证book_id是否为整数，防止SQL注入
    $book_id = intval($book_id);
    if ($book_id <= 0) {
        $download_error = '无效的书籍ID';
    } else {
        // 先获取书籍信息，验证书籍是否存在
        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$book) {
            $download_error = '书籍不存在';
        } else {
            // 使用原子操作尝试记录下载日志（如果超过限制会自动拒绝）
            // 这个操作会在数据库层面使用锁，防止并发绕过
            if (!try_log_download($user_id, $book_id)) {
                $download_error = '今日下载次数已达上限（5次），请明天再试';
            } else {
                // 下载日志已成功记录，现在提供文件下载
                $filepath = __DIR__ . $book['file_path'];
                
                // 安全检查：确保文件路径在预期目录内，防止目录遍历攻击
                $real_path = realpath($filepath);
                $base_dir = realpath(__DIR__);
                
                if (!$real_path || strpos($real_path, $base_dir) !== 0) {
                    $download_error = '文件路径无效';
                } elseif (!file_exists($filepath)) {
                    $download_error = '文件不存在';
                } else {
                    // 清除任何之前的输出
                    if (ob_get_level()) {
                        ob_end_clean();
                    }
                    
                    // 设置下载头
                    switch (strtolower($book['file_format'])) {
                        case 'pdf':
                            header('Content-Type: application/pdf');
                            break;
                        case 'epub':
                            header('Content-Type: application/epub+zip');
                            break;
                        case 'mobi':
                            header('Content-Type: application/x-mobipocket-ebook');
                            break;
                        case 'azw3':
                            header('Content-Type: application/vnd.amazon.ebook');
                            break;
                        case 'djvu':
                            header('Content-Type: image/vnd.djvu');
                            break;
                        case 'txt':
                            header('Content-Type: text/plain; charset=utf-8');
                            break;
                        case 'zip':
                            header('Content-Type: application/zip');
                            break;
                        case 'rar':
                            header('Content-Type: application/vnd.rar');
                            break;
                        default:
                            header('Content-Type: application/octet-stream');
                    }
                    
                    // 防止浏览器直接显示文件内容
                    header('Content-Disposition: attachment; filename="' . basename($book['file_name']) . '"');
                    header('Content-Length: ' . filesize($filepath));
                    header('Cache-Control: no-cache, must-revalidate');
                    header('Pragma: no-cache');
                    header('Expires: 0');
                    
                    // 输出文件内容
                    readfile($filepath);
                    exit;
                }
            }
        }
    }
}

// 搜索相关代码
if ($action === 'search') {
    // 获取搜索参数（如果之前已初始化则使用已有值）
    $query = trim($query);
    $type = $type ?? 'all';
    $format = $format ?? ''; // 新增文件格式参数
    $page = max(1, intval($_GET['page'] ?? 1));

    $limit = 20;
    $offset = ($page - 1) * $limit;

    // 如果之前已初始化，这里会重新赋值
    $books = [];
    $total = 0;

    if (!empty($query)) {

        // 构建搜索条件
        $search_conditions = build_search_conditions($type, $query, $format); // 传递format参数

        // 获取总数
        $count_sql = "SELECT COUNT(DISTINCT b.id) FROM books b WHERE {$search_conditions['where']}";
        $stmt = $pdo->prepare($count_sql);
        $stmt->execute($search_conditions['params']);
        $total = $stmt->fetchColumn();

        // 获取分页数据
        $sql = "SELECT DISTINCT b.id, b.title, b.author, b.description, b.cover_url, b.published_at, b.file_format, b.file_size,
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
        
        // 记录搜索历史（只在第一页时记录，避免重复记录）
        if ($page === 1) {
            $user_id = is_logged_in() ? get_user_id() : null;
            log_search_history($query, $type, $total, $user_id);
        }

    } else {
        // 没有搜索关键词时，显示最新的书籍（支持分页）
        $start_time = microtime(true);

        // 获取总数
        $count_sql = "SELECT COUNT(*) FROM books";
        $stmt = $pdo->prepare($count_sql);
        $stmt->execute();
        $total = $stmt->fetchColumn();

        // 获取分页数据
        $sql = "SELECT * FROM `books` ORDER BY `id` Desc LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    }
}

?>

<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="format-detection" content="telephone=no">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <?php 
    $current_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $canonical_url = $current_url;
    // 移除page参数来生成canonical URL（如果page=1）
    if (isset($_GET['page']) && $_GET['page'] == 1) {
        $params = $_GET;
        unset($params['page']);
        $canonical_url = 'https://' . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');
        if (!empty($params)) {
            $canonical_url .= '?' . http_build_query($params);
        }
    }
    ?>
    
    <?php if ($action === 'detail' && $book): ?>
        <title><?php echo htmlspecialchars($book['title']); ?> - <?php echo htmlspecialchars($book['author']); ?> - 七秒电子书</title>
        <meta name="description" content="<?php echo htmlspecialchars(mb_substr(strip_tags($book['description'] ?: '暂无简介'), 0, 155)); ?>">
        <meta name="keywords" content="<?php echo htmlspecialchars($book['title']); ?>,<?php echo htmlspecialchars($book['author']); ?>,电子书下载,<?php echo htmlspecialchars($book['category'] ?? ''); ?>,<?php echo htmlspecialchars($book['file_format']); ?>电子书,免费电子书">
        <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
        
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="book">
        <meta property="og:url" content="<?php echo htmlspecialchars($current_url); ?>">
        <meta property="og:title" content="<?php echo htmlspecialchars($book['title']); ?> - <?php echo htmlspecialchars($book['author']); ?>">
        <meta property="og:description" content="<?php echo htmlspecialchars(mb_substr(strip_tags($book['description'] ?: '暂无简介'), 0, 200)); ?>">
        <meta property="og:image" content="<?php echo "https://" . $_SERVER['HTTP_HOST'] . $book['cover_url']; ?>">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:site_name" content="七秒电子书">
        <meta property="og:locale" content="zh_CN">
        
        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:url" content="<?php echo htmlspecialchars($current_url); ?>">
        <meta name="twitter:title" content="<?php echo htmlspecialchars($book['title']); ?> - <?php echo htmlspecialchars($book['author']); ?>">
        <meta name="twitter:description" content="<?php echo htmlspecialchars(mb_substr(strip_tags($book['description'] ?: '暂无简介'), 0, 200)); ?>">
        <meta name="twitter:image" content="<?php echo "https://" . $_SERVER['HTTP_HOST'] . $book['cover_url']; ?>">
        
        <!-- Book Schema.org Structured Data -->
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Book",
            "name": <?php echo json_encode($book['title'], JSON_UNESCAPED_UNICODE); ?>,
            "author": {
                "@type": "Person",
                "name": <?php echo json_encode($book['author'], JSON_UNESCAPED_UNICODE); ?>
            },
            "description": <?php echo json_encode(mb_substr(strip_tags($book['description'] ?: '暂无简介'), 0, 500), JSON_UNESCAPED_UNICODE); ?>,
            "image": "https://<?php echo $_SERVER['HTTP_HOST'] . $book['cover_url']; ?>",
            "bookFormat": "<?php echo htmlspecialchars($book['file_format']); ?>",
            "inLanguage": "zh-CN",
            "publisher": {
                "@type": "Organization",
                "name": <?php echo json_encode($book['publisher'] ?? '七秒电子书', JSON_UNESCAPED_UNICODE); ?>
            },
            "datePublished": <?php echo json_encode($book['published_at'] ?? date('Y-m-d'), JSON_UNESCAPED_UNICODE); ?>,
            "offers": {
                "@type": "Offer",
                "price": "0",
                "priceCurrency": "CNY",
                "availability": "https://schema.org/InStock"
            }
        }
        </script>
        
        <!-- Breadcrumb Schema.org -->
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "BreadcrumbList",
            "itemListElement": [{
                "@type": "ListItem",
                "position": 1,
                "name": "首页",
                "item": "https://<?php echo $_SERVER['HTTP_HOST']; ?>/"
            }, {
                "@type": "ListItem",
                "position": 2,
                "name": <?php echo json_encode($book['title'], JSON_UNESCAPED_UNICODE); ?>,
                "item": "<?php echo htmlspecialchars($current_url); ?>"
            }]
        }
        </script>
    <?php else: ?>
        <title><?php echo $query ? htmlspecialchars($query) . ' - 电子书搜索结果 - 七秒电子书' : '七秒电子书 - 地表最强电子书搜索引擎'; ?></title>
        <meta name="description" content="<?php echo $query ? '搜索"' . htmlspecialchars($query) . '"的电子书结果，提供PDF、EPUB、MOBI等格式免费下载' : '七秒电子书提供海量免费电子书资源，支持PDF、EPUB、MOBI等格式一键搜索与高速下载，涵盖小说、教材、技术书籍等，打造便捷阅读体验。'; ?>">
        <meta name="keywords" content="电子书下载, 免费电子书, 电子书搜索, PDF电子书, EPUB下载, 七秒电子书, 小说电子书, 电子书资源站<?php echo $query ? ',' . htmlspecialchars($query) : ''; ?>">
        <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
        
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?php echo htmlspecialchars($current_url); ?>">
        <meta property="og:title" content="<?php echo $query ? htmlspecialchars($query) . ' - 电子书搜索结果 - 七秒电子书' : '七秒电子书 - 地表最强电子书搜索引擎'; ?>">
        <meta property="og:description" content="<?php echo $query ? '搜索"' . htmlspecialchars($query) . '"的电子书结果，提供PDF、EPUB、MOBI等格式免费下载' : '七秒电子书提供海量免费电子书资源，支持PDF、EPUB、MOBI等格式一键搜索与高速下载，涵盖小说、教材、技术书籍等，打造便捷阅读体验。'; ?>">
        <meta property="og:site_name" content="七秒电子书">
        <meta property="og:locale" content="zh_CN">
        
        <!-- Website Schema.org Structured Data -->
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebSite",
            "name": "七秒电子书",
            "url": "https://<?php echo $_SERVER['HTTP_HOST']; ?>/",
            "potentialAction": {
                "@type": "SearchAction",
                "target": "https://<?php echo $_SERVER['HTTP_HOST']; ?>/?q={search_term_string}",
                "query-input": "required name=search_term_string"
            }
        }
        </script>
        
        <?php if ($query): ?>
        <!-- SearchResultsPage Schema.org -->
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "SearchResultsPage",
            "url": "<?php echo htmlspecialchars($current_url); ?>",
            "mainEntity": {
                "@type": "ItemList",
                "numberOfItems": <?php echo $total ?? 0; ?>,
                "itemListElement": [
                    <?php if (!empty($books)): ?>
                    <?php foreach (array_slice($books, 0, 10) as $idx => $book_item): ?>
                    {
                        "@type": "ListItem",
                        "position": <?php echo $idx + 1; ?>,
                        "item": {
                            "@type": "Book",
                            "name": <?php echo json_encode($book_item['title'], JSON_UNESCAPED_UNICODE); ?>,
                            "author": <?php echo json_encode($book_item['author'], JSON_UNESCAPED_UNICODE); ?>,
                            "url": "https://<?php echo $_SERVER['HTTP_HOST']; ?>/?action=detail&id=<?php echo $book_item['id']; ?>"
                        }
                    }<?php echo $idx < min(9, count($books) - 1) ? ',' : ''; ?>
                    <?php endforeach; ?>
                    <?php endif; ?>
                ]
            }
        }
        </script>
        <?php endif; ?>
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* 移动端优化 */
        @media (max-width: 640px) {
            /* 确保触摸目标至少44x44px */
            button, a[role="button"], input[type="submit"], input[type="button"] {
                min-height: 44px;
                min-width: 44px;
            }
            /* 优化字体大小，避免缩放 */
            body {
                -webkit-text-size-adjust: 100%;
                -moz-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
                text-size-adjust: 100%;
            }
        }
        /* 性能优化 */
        img {
            max-width: 100%;
            height: auto;
        }
        /* 平滑滚动 */
        html {
            scroll-behavior: smooth;
        }
        /* 焦点可见性 */
        *:focus-visible {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }
    </style>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-S1VQ1GTE0R"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-S1VQ1GTE0R');
    </script>
    <script type="text/javascript">
        (function(c, l, a, r, i, t, y) {
            c[a] = c[a] || function() {
                (c[a].q = c[a].q || []).push(arguments)
            };
            t = l.createElement(r);
            t.async = 1;
            t.src = "https://www.clarity.ms/tag/" + i;
            y = l.getElementsByTagName(r)[0];
            y.parentNode.insertBefore(t, y);
        })(window, document, "clarity", "script", "u6azio9pp0");
    </script>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6790232035711874"
     crossorigin="anonymous"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <!-- 导航栏 -->
    <nav class="bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg sticky top-0 z-50" role="navigation" aria-label="主导航">
        <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8">
            <div class="flex justify-between items-center h-14 sm:h-16">
                <div class="flex items-center flex-shrink-0">
                    <a href="./" class="flex items-center focus:outline-none focus:ring-2 focus:ring-white rounded" aria-label="返回首页">
                        <i class="fas fa-book text-xl sm:text-2xl mr-2" aria-hidden="true"></i>
                        <span class="text-lg sm:text-xl font-bold">七秒电子书</span>
                    </a>
                </div>
                <div class="flex items-center gap-1 sm:gap-3">
                    <!-- 新增导航链接 -->
                    <a href="?action=popular_searches" class="flex items-center bg-white/20 hover:bg-white/30 px-1.5 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition focus:outline-none focus:ring-2 focus:ring-white whitespace-nowrap" aria-label="热门搜索">
                        <i class="fas fa-fire sm:mr-1" aria-hidden="true"></i><span class="hidden sm:inline">热门搜索</span>
                    </a>
                    <a href="?action=download_ranking" class="flex items-center bg-white/20 hover:bg-white/30 px-1.5 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition focus:outline-none focus:ring-2 focus:ring-white whitespace-nowrap" aria-label="下载排行">
                        <i class="fas fa-trophy sm:mr-1" aria-hidden="true"></i><span class="hidden sm:inline">下载排行</span>
                    </a>
                    
                    <?php if (is_logged_in()): ?>
                        <div class="hidden sm:flex items-center gap-2 text-xs sm:text-sm">
                            <a href="?action=profile" class="hover:underline focus:outline-none focus:ring-2 focus:ring-white rounded px-1" aria-label="个人资料">
                                <span>欢迎, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                            </a>
                            <?php if (get_user_role() === 'user'): ?>
                                <span class="bg-green-500 px-2 py-1 rounded text-xs whitespace-nowrap">已下载: <?php echo get_user_download_count(); ?>/5</span>
                            <?php endif; ?>
                        </div>
                        <?php if (is_admin()): ?>
                            <a href="admin.php" class="bg-purple-500 hover:bg-purple-600 px-2 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition focus:outline-none focus:ring-2 focus:ring-white whitespace-nowrap" aria-label="管理后台">
                                <i class="fas fa-cog mr-1" aria-hidden="true"></i><span class="hidden sm:inline">管理后台</span>
                            </a>
                        <?php endif; ?>
                        <a href="?logout=1" class="bg-red-500 hover:bg-red-600 px-2 sm:px-3 py-1.5 sm:py-2 rounded text-xs sm:text-sm transition focus:outline-none focus:ring-2 focus:ring-white whitespace-nowrap" aria-label="退出登录">
                            <i class="fas fa-sign-out-alt sm:mr-1" aria-hidden="true"></i><span class="hidden sm:inline">退出</span>
                        </a>
                    <?php else: ?>
                        <span class="hidden sm:inline text-xs sm:text-sm">游客</span>
                        <a href="?action=login" class="bg-green-500 hover:bg-green-600 px-3 sm:px-4 py-1.5 sm:py-2 rounded text-xs sm:text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-white whitespace-nowrap" aria-label="用户登录">
                            <i class="fas fa-user sm:mr-1" aria-hidden="true"></i><span>登录</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <?php 
    // 获取热门搜索关键词（在搜索页面和首页显示）
    $popular_searches = [];
    if ($action === 'search' || (empty($action) || $action === 'search')) {
        $popular_searches = get_popular_searches(10);
    }
    ?>
    
    <?php if (!empty($popular_searches) && ($action === 'search' || empty($action) || $action === 'search')): ?>
        <!-- 热门搜索 -->
        <div class="bg-white border-b border-gray-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8 py-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs sm:text-sm text-gray-600 font-medium whitespace-nowrap">
                        <i class="fas fa-fire text-orange-500 mr-1" aria-hidden="true"></i>热门搜索：
                    </span>
                    <?php foreach ($popular_searches as $popular): ?>
                        <a href="?q=<?php echo urlencode($popular['search_query']); ?>" 
                           class="inline-flex items-center px-2 sm:px-3 py-1 text-xs sm:text-sm bg-gray-100 hover:bg-blue-100 text-gray-700 hover:text-blue-700 rounded-full transition-colors whitespace-nowrap">
                            <?php echo htmlspecialchars($popular['search_query']); ?>
                            <span class="ml-1 text-gray-500 text-xs">(<?php echo $popular['search_count']; ?>)</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($action === 'login'): ?>
        <!-- 登录页面 -->
        <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">用户登录</h1>
                        <a href="./" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-arrow-left mr-1"></i> 返回首页
                        </a>
                    </div>

                    <?php if (isset($login_error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                            <?php echo htmlspecialchars($login_error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="login">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="username">用户名</label>
                            <input type="text" name="username" id="username" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="password">密码</label>
                            <input type="password" name="password" id="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-semibold transition">
                            登录
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <a href="?action=forgot_password" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            忘记密码？
                        </a>
                    </div>

                    <div class="text-center mt-4">
                        <p class="text-gray-600">
                            还没有账号？
                            <a href="?action=register" class="text-blue-600 hover:text-blue-800 font-medium">
                                立即注册
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($action === 'register'): ?>
        <!-- 注册页面 -->
        <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">用户注册</h1>
                        <a href="./" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-arrow-left mr-1"></i> 返回首页
                        </a>
                    </div>

                    <?php if (isset($register_error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                            <?php echo htmlspecialchars($register_error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($register_success)): ?>
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                            <?php echo htmlspecialchars($register_success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="register">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="reg_username">用户名</label>
                            <input type="text" name="reg_username" id="reg_username" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="reg_email">邮箱</label>
                            <input type="email" name="reg_email" id="reg_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="reg_password">密码</label>
                            <input type="password" name="reg_password" id="reg_password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required minlength="6" maxlength="32">
                            <p class="text-gray-500 text-xs mt-1">密码长度为6-32个字符</p>
                        </div>
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg font-semibold transition">
                            注册
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <p class="text-gray-600">已有账号？</p>
                        <a href="?action=login" class="text-blue-600 hover:text-blue-800 font-medium">
                            立即登录
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($action === 'forgot_password'): ?>
        <!-- 忘记密码页面 -->
        <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">忘记密码</h1>
                        <a href="./" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-arrow-left mr-1"></i> 返回首页
                        </a>
                    </div>

                    <?php if (isset($forgot_password_error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                            <?php echo htmlspecialchars($forgot_password_error); ?>
                        </div>
                    <?php elseif (isset($forgot_password_message)): ?>
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                            <?php echo htmlspecialchars($forgot_password_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="forgot_password">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="forgot_email">邮箱地址</label>
                            <input type="email" name="email" id="forgot_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-semibold transition">
                            发送重置链接
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <a href="?action=login" class="text-blue-600 hover:text-blue-800 font-medium">
                            返回登录
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($action === 'reset_password'): ?>
        <!-- 重置密码页面 -->
        <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">重置密码</h1>
                        <a href="./" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-arrow-left mr-1"></i> 返回首页
                        </a>
                    </div>

                    <?php if (isset($reset_password_error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                            <?php echo htmlspecialchars($reset_password_error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($reset_password_success)): ?>
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                            <?php echo htmlspecialchars($reset_password_success); ?>
                        </div>
                        <div class="text-center">
                            <a href="?action=login" class="text-blue-600 hover:text-blue-800 font-medium">前往登录</a>
                        </div>
                    <?php elseif (isset($reset_token) && isset($reset_email)): ?>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="reset_password">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($reset_token); ?>">

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">邮箱</label>
                                <input type="email" value="<?php echo htmlspecialchars($reset_email); ?>" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100">
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">新密码</label>
                                <input type="password" name="password" id="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">确认新密码</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>

                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-semibold transition">
                                重置密码
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                            无效的重置请求
                        </div>
                        <div class="text-center">
                            <a href="./" class="text-blue-600 hover:text-blue-800 font-medium">返回首页</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php elseif (isset($_GET['action']) && $_GET['action'] === 'profile' && is_logged_in()): ?>
        <!-- 个人资料页面 -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">个人资料</h1>
                        <a href="./" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-arrow-left mr-1"></i> 返回首页
                        </a>
                    </div>

                    <?php
                    $user_info = get_user_info(get_user_id());
                    $profile_message = '';
                    $profile_error = '';

                    // 处理表单提交
                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
                        $current_password = $_POST['current_password'] ?? '';
                        $new_password = $_POST['new_password'] ?? '';
                        $confirm_password = $_POST['confirm_password'] ?? '';
                        $email = trim($_POST['email'] ?? '');

                        // 验证当前密码
                        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                        $stmt->execute([get_user_id()]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($user && password_verify($current_password, $user['password_hash'])) {
                        // 更新邮箱（如果提供了新邮箱）
                        if ($email && $email !== $user_info['email']) {
                            // 统一转为小写
                            $email = strtolower(trim($email));
                            
                            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                // 检查邮箱是否已被其他用户使用（使用小写比对）
                                $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ? AND id != ?");
                                $stmt->execute([$email, get_user_id()]);
                                if ($stmt->fetch()) {
                                    $profile_error = '该邮箱已被其他用户使用。';
                                } else {
                                    if (update_user_email(get_user_id(), $email)) {
                                        $profile_message = '邮箱更新成功。';
                                        $user_info['email'] = $email; // 更新显示的邮箱
                                    } else {
                                        $profile_error = '邮箱更新失败，请稍后再试。';
                                    }
                                }
                            } else {
                                $profile_error = '请输入有效的邮箱地址。';
                            }
                        }

                            // 更新密码（如果提供了新密码）
                            if ($new_password) {
                                $password_validation = validate_password($new_password);
                                if (!$password_validation['valid']) {
                                    $profile_error = $password_validation['error'];
                                } elseif ($new_password !== $confirm_password) {
                                    $profile_error = '新密码与确认密码不匹配。';
                                } else {
                                    if (update_user_password(get_user_id(), $new_password)) {
                                        $profile_message = '密码更新成功。';
                                    } else {
                                        $profile_error = '密码更新失败，请稍后再试。';
                                    }
                                }
                            } else if (!$profile_message && !$profile_error) {
                                // 只显示一条消息
                                $profile_message = '资料更新成功。';
                            }
                        } else {
                            $profile_error = '当前密码错误。';
                        }
                    }
                    ?>

                    <?php if ($profile_message): ?>
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                            <?php echo htmlspecialchars($profile_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($profile_error): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                            <?php echo htmlspecialchars($profile_error); ?>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-700 mb-4">基本信息</h2>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="mb-3">
                                    <label class="block text-gray-600 text-sm mb-1">用户名</label>
                                    <div class="font-medium"><?php echo htmlspecialchars($user_info['username']); ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-gray-600 text-sm mb-1">注册时间</label>
                                    <div class="font-medium"><?php echo htmlspecialchars($user_info['created_at']); ?></div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-gray-700 mb-4">修改资料</h2>
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="update_profile">

                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">邮箱地址</label>
                                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="current_password">当前密码 *</label>
                                    <input type="password" name="current_password" id="current_password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                </div>

                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="new_password">新密码</label>
                                    <input type="password" name="new_password" id="new_password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <p class="text-gray-500 text-xs mt-1">6-32个字符，留空则不更改</p>
                                </div>

                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">确认新密码</label>
                                    <input type="password" name="confirm_password" id="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div class="pt-4">
                                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-semibold transition">
                                        更新资料
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($action === 'detail' && $book): ?>
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
                                    <span itemprop="name"><?php echo format_authors($book['author']); ?></span>
                                </span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-2 gap-2 sm:gap-4 mb-4 sm:mb-6">
                                <div class="bg-blue-50 p-2.5 sm:p-3 rounded-lg">
                                    <div class="text-xs sm:text-sm text-gray-500 mb-1">文件类型</div>
                                    <div class="font-semibold text-sm sm:text-base"><?php echo htmlspecialchars($book['file_format']); ?></div>
                                </div>
                                <div class="bg-green-50 p-2.5 sm:p-3 rounded-lg">
                                    <div class="text-xs sm:text-sm text-gray-500 mb-1">文件大小</div>
                                    <div class="font-semibold text-sm sm:text-base"><?php echo format_file_size($book['file_size']); ?></div>
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

                            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                                <?php if (is_logged_in()): ?>
                                    <?php if (check_download_limit(get_user_id())): ?>
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
            <?php echo render_books_list($related_books, $book_layout, $query ?? '', 'relatedBooksList'); ?>
           
        </div>
    <?php elseif ($action === 'popular_searches'): ?>
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
                
                <?php 
                if (empty($popular_searches_list)): ?>
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
    <?php elseif ($action === 'download_ranking'): ?>
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
                
                <?php 
                if (empty($download_ranking_books)): ?>
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
                                                        <img src="<?php echo htmlspecialchars($book['cover_url']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="w-full h-full object-cover">
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
                                                        <span class="text-gray-500"><?php echo format_file_size($book['file_size']); ?></span>
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
                                                    <img src="<?php echo htmlspecialchars($book['cover_url']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="w-full h-full object-cover">
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
    <?php else: ?>
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

                        <!-- 添加文件类型筛选 -->
                        <div class="flex-1 min-w-[140px]">
                            <label for="file-format" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">文件类型</label>
                            <select id="file-format" name="format" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm sm:text-base focus:ring-2 focus:ring-blue-500 focus:border-transparent min-h-[44px]">
                                <option value="">所有格式</option>
                                <?php foreach (SUPPORTED_EXTS as $ext): ?>
                                    <option value="<?php echo $ext; ?>" <?php echo (($_GET['format'] ?? '') === $ext) ? 'selected' : ''; ?>>
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
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-800"><?php echo empty($query) ? '最新图书' : '搜索结果'; ?></h2>
                            <div class="flex items-center justify-between sm:justify-end gap-3">
                                <p class="text-sm sm:text-base text-gray-600 hidden sm:block">
                                    找到 <span class="font-bold text-blue-600"><?php echo number_format($total ?? 0); ?></span> 条结果
                                </p>
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
                    <?php echo render_books_list($books, $book_layout, $query ?? '', 'searchBooksList'); ?>

                    <!-- 分页 -->
                    <?php if ((!empty($query) || empty($query)) && ($total ?? 0) > $limit): ?>
                        <?php
                        $total_pages = ceil($total / $limit);
                        $base_params = array_filter([
                            'q' => $query ?? '',
                            'type' => $type ?? 'all',
                            'format' => $_GET['format'] ?? ''
                        ], function ($value) {
                            return !empty($value) || $value === 0;
                        });
                        echo render_pagination($page, $total_pages, $total, $limit, [
                            'base_params' => $base_params,
                            'preserve_params' => []
                        ], [
                            'show_info' => false, // 已在上面显示
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
    <?php endif; ?>

    <script>
        // Cookie 操作函数
        function setCookie(name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/';
        }

        function getCookie(name) {
            const nameEQ = name + '=';
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }

        // 布局切换函数
        function switchBookLayout(layout, containerId) {
            // 保存到 Cookie
            setCookie('book_layout', layout, 365);
            
            // 重新加载页面以应用新布局
            location.reload();
        }
    </script>
</body>

</html>
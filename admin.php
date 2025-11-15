<?php
// 管理员后台页面


require 'config.php';
session_start();

//只允许管理员访问
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('数据库连接失败');
}

// 已登录，处理管理功能
$action = $_GET['action'] ?? 'dashboard';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// 处理各种管理操作
switch ($action) {
    case 'logout':
        session_destroy();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    case 'users':
        // 获取用户列表
        $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取用户总数
        $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $total_pages = ceil($total_users / $limit);
        break;

    case 'books':
        // 获取电子书列表
        $stmt = $pdo->prepare("
            SELECT b.*, 
                   GROUP_CONCAT(bt.tag_name) as tags
            FROM books b
            LEFT JOIN book_tags bt ON b.id = bt.book_id
            GROUP BY b.id
            ORDER BY b.created_at DESC 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取电子书总数
        $total_books = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
        $total_pages = ceil($total_books / $limit);
        break;

    case 'downloads':
        // 获取下载记录
        $stmt = $pdo->prepare("
            SELECT dl.*, u.username, b.title
            FROM download_logs dl
            LEFT JOIN users u ON dl.user_id = u.id
            LEFT JOIN books b ON dl.book_id = b.id
            ORDER BY dl.download_time DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $downloads = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取下载记录总数
        $total_downloads = $pdo->query("SELECT COUNT(*) FROM download_logs")->fetchColumn();
        $total_pages = ceil($total_downloads / $limit);
        break;

    case 'tags':
        // 获取搜索关键词
        $tag_search = trim($_GET['search'] ?? '');

        // 构建查询条件
        $where_clause = '';
        $params = [];
        if (!empty($tag_search)) {
            $where_clause = 'WHERE bt.tag_name LIKE :search';
            $params[':search'] = '%' . $tag_search . '%';
        }

        // 获取标签列表（按标签名称分组，统计使用次数）
        $sql = "
            SELECT 
                bt.tag_name,
                COUNT(DISTINCT bt.book_id) as usage_count,
                GROUP_CONCAT(DISTINCT b.id ORDER BY b.id SEPARATOR ',') as book_ids,
                GROUP_CONCAT(DISTINCT b.title ORDER BY b.id SEPARATOR ' | ') as book_titles
            FROM book_tags bt
            LEFT JOIN books b ON bt.book_id = b.id
            $where_clause
            GROUP BY bt.tag_name
            ORDER BY usage_count DESC, bt.tag_name ASC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取标签总数
        $count_sql = "SELECT COUNT(DISTINCT tag_name) FROM book_tags $where_clause";
        $count_stmt = $pdo->prepare($count_sql);
        foreach ($params as $key => $value) {
            $count_stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $count_stmt->execute();
        $total_tags = $count_stmt->fetchColumn();
        $total_pages = ceil($total_tags / $limit);
        break;

    case 'admin_settings':
        // 获取当前管理员信息
        $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
        break;

    case 'edit_book':
        // 编辑电子书页面
        $book_id = intval($_GET['id'] ?? 0);
        if ($book_id) {
            $stmt = $pdo->prepare("
                SELECT b.*, 
                       GROUP_CONCAT(bt.tag_name) as tags
                FROM books b
                LEFT JOIN book_tags bt ON b.id = bt.book_id
                WHERE b.id = ?
                GROUP BY b.id
            ");
            $stmt->execute([$book_id]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$book) {
                $error = '电子书不存在';
                $action = 'books';
            }
        } else {
            $error = '未指定电子书ID';
            $action = 'books';
        }
        break;

    default:
        // 仪表盘 - 显示统计信息
        $stats = [
            'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'total_books' => $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn(),
            'total_downloads' => $pdo->query("SELECT COUNT(*) FROM download_logs")->fetchColumn(),
            'today_downloads' => $pdo->query("SELECT COUNT(*) FROM download_logs WHERE DATE(download_time) = CURDATE()")->fetchColumn()
        ];

        // 获取近7天用户增长数据
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as user_count
            FROM users 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at)
        ");
        $stmt->execute();
        $user_growth_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 构建完整的7天数据
        $daily_user_counts = [];
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dates[] = $date;
            $daily_user_counts[$date] = 0;
        }

        // 填充实际数据
        foreach ($user_growth_data as $data) {
            $daily_user_counts[$data['date']] = (int)$data['user_count'];
        }

        // 获取近7天电子书增长数据
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as book_count
            FROM books 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at)
        ");
        $stmt->execute();
        $book_growth_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 构建完整的7天电子书数据
        $daily_book_counts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $daily_book_counts[$date] = 0;
        }

        // 填充实际数据
        foreach ($book_growth_data as $data) {
            $daily_book_counts[$data['date']] = (int)$data['book_count'];
        }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_action = $_POST['form_action'] ?? '';

    switch ($post_action) {
        case 'add_user':
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'user';

            if ($username && $password && $email) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $password_hash, $email, $role]);
                $message = '用户添加成功';
            } else {
                $error = '请填写完整信息';
            }
            break;

        case 'edit_user':
            $user_id = intval($_POST['user_id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'user';
            $new_password = $_POST['password'] ?? '';

            if ($user_id && $username && $email) {
                if ($new_password) {
                    // 如果提供了新密码，则更新密码
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, password_hash = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $role, $password_hash, $user_id]);
                } else {
                    // 否则只更新其他字段
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $role, $user_id]);
                }
                $message = '用户信息更新成功';
            } else {
                $error = '请填写完整信息';
            }
            break;

        case 'delete_user':
            $user_id = intval($_POST['user_id'] ?? 0);
            if ($user_id) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = '用户删除成功';
            }
            break;

        case 'add_book':
            $title = trim($_POST['title'] ?? '');
            $author = trim($_POST['author'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $publisher = trim($_POST['publisher'] ?? '');
            $page_count = intval($_POST['page_count'] ?? 0);
            $file_name = trim($_POST['file_name'] ?? '');
            $file_path = trim($_POST['file_path'] ?? '');
            $file_size = trim($_POST['file_size'] ?? '');
            $file_format = trim($_POST['file_format'] ?? '');
            $tags = trim($_POST['tags'] ?? '');

            if ($title && $author) {
                $current_time = date('Y-m-d H:i:s');
                $stmt = $pdo->prepare("
                    INSERT INTO books (title, author, description, category, publisher, page_count, file_name, file_path, file_size, file_format, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $author, $description, $category, $publisher, $page_count, $file_name, $file_path, $file_size, $file_format, $current_time]);

                $book_id = $pdo->lastInsertId();

                // 添加标签
                if ($tags) {
                    $tag_list = array_filter(array_map('trim', explode(',', $tags)));
                    foreach ($tag_list as $tag) {
                        if ($tag) {
                            $tag_stmt = $pdo->prepare("INSERT INTO book_tags (book_id, tag_name) VALUES (?, ?)");
                            $tag_stmt->execute([$book_id, $tag]);
                        }
                    }
                }

                $message = '电子书添加成功';
            } else {
                $error = '请填写必填字段（标题、作者）';
            }
            break;

        case 'edit_book':
            $book_id = intval($_POST['book_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $author = trim($_POST['author'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $publisher = trim($_POST['publisher'] ?? '');
            $page_count = intval($_POST['page_count'] ?? 0);
            $file_name = trim($_POST['file_name'] ?? '');
            $file_path = trim($_POST['file_path'] ?? '');
            $file_size = trim($_POST['file_size'] ?? '');
            $file_format = trim($_POST['file_format'] ?? '');
            $tags = trim($_POST['tags'] ?? '');

            if ($book_id && $title && $author) {
                $current_time = date('Y-m-d H:i:s');
                $stmt = $pdo->prepare("
                    UPDATE books 
                    SET title = ?, author = ?, description = ?, category = ?, publisher = ?, page_count = ?, 
                        file_name = ?, file_path = ?, file_size = ?, file_format = ?, updated_at = ?
                    WHERE id = ?
                ");
                $stmt->execute([$title, $author, $description, $category, $publisher, $page_count, $file_name, $file_path, $file_size, $file_format, $current_time, $book_id]);

                // 删除原有标签
                $stmt = $pdo->prepare("DELETE FROM book_tags WHERE book_id = ?");
                $stmt->execute([$book_id]);

                // 添加新标签
                if ($tags) {
                    $tag_list = array_filter(array_map('trim', explode(',', $tags)));
                    foreach ($tag_list as $tag) {
                        if ($tag) {
                            $tag_stmt = $pdo->prepare("INSERT INTO book_tags (book_id, tag_name) VALUES (?, ?)");
                            $tag_stmt->execute([$book_id, $tag]);
                        }
                    }
                }

                $message = '电子书信息更新成功';
                // 重定向到书籍列表页面
                header('Location: ?action=books');
                exit;
            } else {
                $error = '请填写必填字段（标题、作者）';
            }
            break;

        case 'delete_book':
            $book_id = intval($_POST['book_id'] ?? 0);
            if ($book_id) {
                $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
                $stmt->execute([$book_id]);
                $message = '电子书删除成功';
            }
            break;

        case 'add_tag':
            $book_id = intval($_POST['book_id'] ?? 0);
            $tag_name = trim($_POST['tag_name'] ?? '');

            if ($book_id && $tag_name) {
                $stmt = $pdo->prepare("INSERT INTO book_tags (book_id, tag_name) VALUES (?, ?)");
                $stmt->execute([$book_id, $tag_name]);
                $message = '标签添加成功';
            } else {
                $error = '请选择图书并输入标签名称';
            }
            break;

        case 'delete_tag':
            $tag_name = trim($_POST['tag_name'] ?? '');
            if ($tag_name) {
                // 删除该标签的所有关联
                $stmt = $pdo->prepare("DELETE FROM book_tags WHERE tag_name = ?");
                $stmt->execute([$tag_name]);
                $message = '标签删除成功';
            }
            break;

        case 'edit_tag':
            $old_tag_name = trim($_POST['old_tag_name'] ?? '');
            $new_tag_name = trim($_POST['new_tag_name'] ?? '');

            if ($old_tag_name && $new_tag_name && $old_tag_name !== $new_tag_name) {
                // 检查新标签名是否已存在
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM book_tags WHERE tag_name = ?");
                $check_stmt->execute([$new_tag_name]);
                $exists = $check_stmt->fetchColumn();

                if ($exists > 0) {
                    // 如果新标签已存在，则合并：先更新为新标签名，然后删除重复项
                    $update_stmt = $pdo->prepare("UPDATE book_tags SET tag_name = ? WHERE tag_name = ?");
                    $update_stmt->execute([$new_tag_name, $old_tag_name]);

                    // 删除重复的标签关联（同一个book_id不应该有重复的tag_name）
                    $delete_dup_stmt = $pdo->prepare("
                        DELETE t1 FROM book_tags t1
                        INNER JOIN book_tags t2 
                        WHERE t1.id > t2.id 
                        AND t1.tag_name = t2.tag_name 
                        AND t1.book_id = t2.book_id
                    ");
                    $delete_dup_stmt->execute();

                    $message = '标签已重命名并合并';
                } else {
                    // 直接重命名标签
                    $update_stmt = $pdo->prepare("UPDATE book_tags SET tag_name = ? WHERE tag_name = ?");
                    $update_stmt->execute([$new_tag_name, $old_tag_name]);
                    $message = '标签重命名成功';
                }
            } else {
                $error = '标签名称不能为空或相同';
            }
            break;

        case 'merge_tags':
            $source_tag = trim($_POST['source_tag'] ?? '');
            $target_tag = trim($_POST['target_tag'] ?? '');

            if ($source_tag && $target_tag && $source_tag !== $target_tag) {
                // 检查目标标签是否存在
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM book_tags WHERE tag_name = ?");
                $check_stmt->execute([$target_tag]);
                $target_exists = $check_stmt->fetchColumn();

                if ($target_exists == 0) {
                    // 如果目标标签不存在，先创建它（通过更新源标签）
                    $update_stmt = $pdo->prepare("UPDATE book_tags SET tag_name = ? WHERE tag_name = ?");
                    $update_stmt->execute([$target_tag, $source_tag]);
                    $message = '标签合并成功（源标签已重命名为目标标签）';
                } else {
                    // 合并标签：将源标签的所有书籍关联到目标标签
                    // 先找出源标签的书籍ID（排除已存在于目标标签的书籍）
                    // 获取当前时间
                    $current_time = date('Y-m-d H:i:s');
                    $merge_stmt = $pdo->prepare("
                        INSERT INTO book_tags (book_id, tag_name, created_at)
                        SELECT DISTINCT bt1.book_id, ?, ?
                        FROM book_tags bt1
                        WHERE bt1.tag_name = ?
                        AND NOT EXISTS (
                            SELECT 1 FROM book_tags bt2 
                            WHERE bt2.book_id = bt1.book_id 
                            AND bt2.tag_name = ?
                        )
                    ");
                    $merge_stmt->execute([$target_tag, $current_time, $source_tag, $target_tag]);

                    // 删除源标签的所有关联
                    $delete_stmt = $pdo->prepare("DELETE FROM book_tags WHERE tag_name = ?");
                    $delete_stmt->execute([$source_tag]);

                    $message = '标签合并成功';
                }
            } else {
                $error = '请选择要合并的标签，且源标签和目标标签不能相同';
            }
            break;

        case 'update_admin':
            $admin_id = intval($_POST['admin_id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($admin_id && $username && $email) {
                if ($password) {
                    // 如果提供了新密码，则更新密码
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password_hash = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $password_hash, $admin_id]);
                } else {
                    // 否则只更新用户名和邮箱
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $admin_id]);
                }
                $message = '管理员信息更新成功';
                $_SESSION['admin_username'] = $username;
            } else {
                $error = '请填写用户名和邮箱';
            }
            break;
    }
}

// 获取分页URL
function build_page_url($action, $new_page)
{
    $params = ['action' => $action, 'page' => $new_page];
    return '?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] ? '七秒电子书后台管理' : '管理员登录'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-sidebar {
            width: 250px;
            position: fixed;
            height: 100vh;
            background: #2d3748;
            color: white;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 100;
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }

            .admin-sidebar.active {
                transform: translateX(0);
            }
        }

        .admin-content {
            margin-left: 250px;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .admin-content {
                margin-left: 0;
            }
        }

        .admin-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .admin-table th {
            background-color: #f7fafc;
            font-weight: 600;
        }

        .admin-table tr:hover {
            background-color: #f7fafc;
        }

        .admin-btn {
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }

        .admin-btn-primary {
            background-color: #3182ce;
            color: white;
        }

        .admin-btn-primary:hover {
            background-color: #2c5282;
        }

        .admin-btn-danger {
            background-color: #e53e3e;
            color: white;
        }

        .admin-btn-danger:hover {
            background-color: #c53030;
        }

        .admin-btn-success {
            background-color: #38a169;
            color: white;
        }

        .admin-btn-success:hover {
            background-color: #2f855a;
        }

        .admin-form-group {
            margin-bottom: 15px;
        }

        .admin-form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .admin-form-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 14px;
        }

        .admin-form-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 14px;
        }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .login-card {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 99;
            background: #2d3748;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .sidebar-toggle {
                display: block;
            }
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99;
        }
    </style>
</head>

<body class="bg-gray-100">
    <!-- 侧边栏切换按钮 -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- 遮罩层 -->
    <div class="overlay" id="overlay"></div>

    <!-- 侧边栏 -->
    <div class="admin-sidebar" id="adminSidebar">
        <div class="p-6">
            <h1 class="text-xl font-bold mb-6 flex items-center">
                <i class="fas fa-book mr-2"></i>
                七秒后台
            </h1>
        </div>

        <div class="px-4">
            <a href="?action=dashboard" class="block py-3 px-4 rounded hover:bg-gray-700 <?php echo $action === 'dashboard' ? 'bg-gray-700' : ''; ?>">
                <i class="fas fa-tachometer-alt mr-2"></i> 仪表盘
            </a>
            <a href="?action=users" class="block py-3 px-4 rounded hover:bg-gray-700 <?php echo $action === 'users' ? 'bg-gray-700' : ''; ?>">
                <i class="fas fa-users mr-2"></i> 用户管理
            </a>
            <a href="?action=books" class="block py-3 px-4 rounded hover:bg-gray-700 <?php echo $action === 'books' ? 'bg-gray-700' : ''; ?>">
                <i class="fas fa-book mr-2"></i> 电子书管理
            </a>
            <a href="?action=downloads" class="block py-3 px-4 rounded hover:bg-gray-700 <?php echo $action === 'downloads' ? 'bg-gray-700' : ''; ?>">
                <i class="fas fa-download mr-2"></i> 下载记录
            </a>
            <a href="?action=tags" class="block py-3 px-4 rounded hover:bg-gray-700 <?php echo $action === 'tags' ? 'bg-gray-700' : ''; ?>">
                <i class="fas fa-tags mr-2"></i> 标签管理
            </a>
            <a href="?action=admin_settings" class="block py-3 px-4 rounded hover:bg-gray-700 <?php echo $action === 'admin_settings' ? 'bg-gray-700' : ''; ?>">
                <i class="fas fa-cog mr-2"></i> 管理员设置
            </a>
            <a href="?action=logout" class="block py-3 px-4 rounded hover:bg-gray-700 mt-4">
                <i class="fas fa-sign-out-alt mr-2"></i> 退出登录
            </a>
        </div>
    </div>

    <!-- 主内容 -->
    <div class="admin-content">
        <?php if (isset($message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($action === 'dashboard'): ?>
            <!-- 仪表盘 -->
            <div class="admin-card">
                <h2 class="text-2xl font-bold mb-6">系统概览</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                        <div class="text-3xl font-bold text-blue-600"><?php echo $stats['total_users']; ?></div>
                        <div class="text-gray-600">总用户数</div>
                    </div>
                    <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                        <div class="text-3xl font-bold text-green-600"><?php echo $stats['total_books']; ?></div>
                        <div class="text-gray-600">电子书数量</div>
                    </div>
                    <div class="bg-yellow-50 p-6 rounded-lg border border-yellow-200">
                        <div class="text-3xl font-bold text-yellow-600"><?php echo $stats['total_downloads']; ?></div>
                        <div class="text-gray-600">总下载次数</div>
                    </div>
                    <div class="bg-purple-50 p-6 rounded-lg border border-purple-200">
                        <div class="text-3xl font-bold text-purple-600"><?php echo $stats['today_downloads']; ?></div>
                        <div class="text-gray-600">今日下载</div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- 近7天用户增长趋势图 -->
                    <div class="bg-white p-6 rounded-lg border">
                        <h3 class="text-lg font-semibold mb-4">近7天用户增长趋势</h3>
                        <canvas id="userGrowthChart" height="100"></canvas>
                    </div>

                    <!-- 近7天电子书增长趋势图 -->
                    <div class="bg-white p-6 rounded-lg border">
                        <h3 class="text-lg font-semibold mb-4">近7天电子书增长趋势</h3>
                        <canvas id="bookGrowthChart" height="100"></canvas>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg border">
                    <h3 class="text-lg font-semibold mb-4">系统信息</h3>
                    <ul class="space-y-2">
                        <li><strong>PHP版本:</strong> <?php echo PHP_VERSION; ?></li>
                        <li><strong>数据库:</strong> MySQL <?php echo $pdo->getAttribute(PDO::ATTR_SERVER_VERSION); ?></li>
                        <li><strong>管理员:</strong> <?php echo $_SESSION['admin_username'] ?? '未知'; ?></li>
                        <li><strong>当前时间:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
                    </ul>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // 用户增长趋势图
                    var userCtx = document.getElementById('userGrowthChart').getContext('2d');
                    var userChart = new Chart(userCtx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($dates); ?>,
                            datasets: [{
                                label: '新增用户数',
                                data: <?php echo json_encode(array_values($daily_user_counts)); ?>,
                                borderColor: 'rgb(54, 162, 235)',
                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                tension: 0.1,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });

                    // 电子书增长趋势图
                    var bookCtx = document.getElementById('bookGrowthChart').getContext('2d');
                    var bookChart = new Chart(bookCtx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($dates); ?>,
                            datasets: [{
                                label: '新增电子书数',
                                data: <?php echo json_encode(array_values($daily_book_counts)); ?>,
                                borderColor: 'rgb(255, 99, 132)',
                                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                tension: 0.1,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });
                });
            </script>

        <?php elseif ($action === 'users'): ?>
            <!-- 用户管理 -->
            <div class="admin-card">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">用户管理</h2>
                    <button onclick="openModal('addUserModal')" class="admin-btn admin-btn-primary">
                        <i class="fas fa-plus mr-2"></i> 添加用户
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户名</th>
                                <th>邮箱</th>
                                <th>角色</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo $user['role'] === 'admin' ? '管理员' : '普通用户'; ?></td>
                                    <td><?php echo $user['created_at']; ?></td>
                                    <td>
                                        <button onclick="openEditUserModal(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>', '<?php echo addslashes($user['email']); ?>', '<?php echo $user['role']; ?>')" class="admin-btn admin-btn-success mr-2">
                                            <i class="fas fa-edit mr-1"></i> 编辑
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('确定要删除此用户吗？')">
                                            <input type="hidden" name="form_action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="admin-btn admin-btn-danger">
                                                <i class="fas fa-trash mr-1"></i> 删除
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 分页 -->
                <?php if ($total_pages > 1): ?>
                    <div class="flex justify-center mt-6">
                        <nav class="flex items-center space-x-2">
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="<?php echo build_page_url('users', $i); ?>" class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-blue-600' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-lg">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($action === 'edit_book' && isset($book)): ?>
            <!-- 编辑电子书页面 -->
            <div class="admin-card">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">编辑电子书</h2>
                    <a href="?action=books" class="admin-btn admin-btn-primary">
                        <i class="fas fa-arrow-left mr-2"></i> 返回列表
                    </a>
                </div>

                <form method="POST" class="max-w-2xl">
                    <input type="hidden" name="form_action" value="edit_book">
                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">

                    <div class="admin-form-group">
                        <label class="admin-form-label">标题 *</label>
                        <input type="text" name="title" class="admin-form-input" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">作者 *</label>
                        <input type="text" name="author" class="admin-form-input" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">简介</label>
                        <textarea name="description" class="admin-form-input" rows="3"><?php echo htmlspecialchars($book['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">类别</label>
                        <input type="text" name="category" class="admin-form-input" value="<?php echo htmlspecialchars($book['category'] ?? ''); ?>">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">出版社</label>
                        <input type="text" name="publisher" class="admin-form-input" value="<?php echo htmlspecialchars($book['publisher'] ?? ''); ?>">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">页数</label>
                        <input type="number" name="page_count" class="admin-form-input" value="<?php echo $book['page_count'] ?? 0; ?>">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">文件名</label>
                        <input type="text" name="file_name" class="admin-form-input" value="<?php echo htmlspecialchars($book['file_name'] ?? ''); ?>">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">文件路径</label>
                        <input type="text" name="file_path" class="admin-form-input" value="<?php echo htmlspecialchars($book['file_path'] ?? ''); ?>">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">文件大小</label>
                        <input type="text" name="file_size" class="admin-form-input" value="<?php echo htmlspecialchars($book['file_size'] ?? ''); ?>">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">文件格式</label>
                        <input type="text" name="file_format" class="admin-form-input" value="<?php echo htmlspecialchars($book['file_format'] ?? ''); ?>">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">标签（逗号分隔）</label>
                        <input type="text" name="tags" class="admin-form-input" value="<?php echo htmlspecialchars($book['tags'] ?? ''); ?>" placeholder="标签1,标签2,标签3">
                    </div>

                    <div class="flex justify-end space-x-2">
                        <a href="?action=books" class="admin-btn admin-btn-danger">取消</a>
                        <button type="submit" class="admin-btn admin-btn-primary">更新电子书</button>
                    </div>
                </form>
            </div>

        <?php elseif ($action === 'add_book_form'): ?>
            <!-- 添加电子书页面 -->
            <div class="admin-card">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">添加电子书</h2>
                    <a href="?action=books" class="admin-btn admin-btn-primary">
                        <i class="fas fa-arrow-left mr-2"></i> 返回列表
                    </a>
                </div>

                <form method="POST" class="max-w-2xl">
                    <input type="hidden" name="form_action" value="add_book">

                    <div class="admin-form-group">
                        <label class="admin-form-label">标题 *</label>
                        <input type="text" name="title" class="admin-form-input" required>
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">作者 *</label>
                        <input type="text" name="author" class="admin-form-input" required>
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">简介</label>
                        <textarea name="description" class="admin-form-input" rows="3"></textarea>
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">类别</label>
                        <input type="text" name="category" class="admin-form-input">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">出版社</label>
                        <input type="text" name="publisher" class="admin-form-input">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">页数</label>
                        <input type="number" name="page_count" class="admin-form-input">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">文件名</label>
                        <input type="text" name="file_name" class="admin-form-input">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">文件路径</label>
                        <input type="text" name="file_path" class="admin-form-input">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">文件大小</label>
                        <input type="text" name="file_size" class="admin-form-input">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">文件格式</label>
                        <input type="text" name="file_format" class="admin-form-input">
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">标签（逗号分隔）</label>
                        <input type="text" name="tags" class="admin-form-input" placeholder="标签1,标签2,标签3">
                    </div>

                    <div class="flex justify-end space-x-2">
                        <a href="?action=books" class="admin-btn admin-btn-danger">取消</a>
                        <button type="submit" class="admin-btn admin-btn-primary">添加电子书</button>
                    </div>
                </form>
            </div>

        <?php elseif ($action === 'books'): ?>
            <!-- 电子书管理 -->
            <div class="admin-card">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">电子书管理</h2>
                    <a href="?action=add_book_form" class="admin-btn admin-btn-primary">
                        <i class="fas fa-plus mr-2"></i> 添加电子书
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>标题</th>
                                <th>作者</th>
                                <th>类别</th>
                                <th>格式</th>
                                <th>大小</th>
                                <th>标签</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><?php echo $book['id']; ?></td>
                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo htmlspecialchars($book['category'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($book['file_format']); ?></td>
                                    <td><?php echo htmlspecialchars($book['file_size']); ?></td>
                                    <td><?php echo htmlspecialchars($book['tags'] ?? ''); ?></td>
                                    <td>
                                        <a href="?action=edit_book&id=<?php echo $book['id']; ?>" class="admin-btn admin-btn-success mr-2">
                                            <i class="fas fa-edit mr-1"></i> 编辑
                                        </a>
                                        <form method="POST" class="inline" onsubmit="return confirm('确定要删除此电子书吗？')">
                                            <input type="hidden" name="form_action" value="delete_book">
                                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                            <button type="submit" class="admin-btn admin-btn-danger">
                                                <i class="fas fa-trash mr-1"></i> 删除
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 分页 -->
                <?php if ($total_pages > 1): ?>
                    <div class="flex justify-center mt-6">
                        <nav class="flex items-center space-x-2">
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="<?php echo build_page_url('books', $i); ?>" class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-blue-600' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-lg">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($action === 'downloads'): ?>
            <!-- 下载记录 -->
            <div class="admin-card">
                <h2 class="text-2xl font-bold mb-6">下载记录</h2>

                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户</th>
                                <th>电子书</th>
                                <th>下载时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($downloads as $download): ?>
                                <tr>
                                    <td><?php echo $download['id']; ?></td>
                                    <td><?php echo htmlspecialchars($download['username'] ?? '游客'); ?></td>
                                    <td><?php echo htmlspecialchars($download['title'] ?? '未知'); ?></td>
                                    <td><?php echo $download['download_time']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 分页 -->
                <?php if ($total_pages > 1): ?>
                    <div class="flex justify-center mt-6">
                        <nav class="flex items-center space-x-2">
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="<?php echo build_page_url('downloads', $i); ?>" class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-blue-600' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-lg">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($action === 'tags'): ?>
            <!-- 标签管理 -->
            <div class="admin-card">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">标签管理</h2>
                    <div class="flex items-center space-x-2">
                        <button onclick="openModal('mergeTagModal')" class="admin-btn admin-btn-warning">
                            <i class="fas fa-compress mr-2"></i> 合并标签
                        </button>
                    </div>
                </div>

                <!-- 搜索框 -->
                <div class="mb-6">
                    <form method="GET" class="flex items-center space-x-2">
                        <input type="hidden" name="action" value="tags">
                        <input type="text"
                            name="search"
                            value="<?php echo htmlspecialchars($tag_search ?? ''); ?>"
                            placeholder="搜索标签名称..."
                            class="admin-form-input flex-1">
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <i class="fas fa-search mr-2"></i> 搜索
                        </button>
                        <?php if (!empty($tag_search)): ?>
                            <a href="?action=tags" class="admin-btn admin-btn-secondary">
                                <i class="fas fa-times mr-2"></i> 清除
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- 统计信息 -->
                <div class="mb-4 text-gray-600">
                    共找到 <strong><?php echo $total_tags ?? 0; ?></strong> 个标签
                    <?php if (!empty($tag_search)): ?>
                        （搜索：<strong><?php echo htmlspecialchars($tag_search); ?></strong>）
                    <?php endif; ?>
                </div>

                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>标签名称</th>
                                <th>使用次数</th>
                                <th>关联书籍预览</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tags)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-8 text-gray-500">
                                        <?php if (!empty($tag_search)): ?>
                                            未找到匹配的标签
                                        <?php else: ?>
                                            暂无标签
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tags as $tag): ?>
                                    <tr>
                                        <td>
                                            <span class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">
                                                <?php echo htmlspecialchars($tag['tag_name']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="font-semibold text-gray-700"><?php echo $tag['usage_count']; ?></span>
                                            <span class="text-gray-500 text-sm">本</span>
                                        </td>
                                        <td class="max-w-md">
                                            <?php
                                            $book_titles = explode(' | ', $tag['book_titles'] ?? '');
                                            $preview_count = 3;
                                            $preview_titles = array_slice($book_titles, 0, $preview_count);
                                            ?>
                                            <div class="text-sm text-gray-600">
                                                <?php if (!empty($preview_titles)): ?>
                                                    <?php foreach ($preview_titles as $title): ?>
                                                        <span class="inline-block mr-2 mb-1">
                                                            <?php echo htmlspecialchars(mb_substr($title, 0, 20)) . (mb_strlen($title) > 20 ? '...' : ''); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                    <?php if (count($book_titles) > $preview_count): ?>
                                                        <span class="text-gray-400">等 <?php echo count($book_titles) - $preview_count; ?> 本...</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-gray-400">暂无关联书籍</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center space-x-2">
                                                <button onclick="openEditTagModal('<?php echo htmlspecialchars($tag['tag_name'], ENT_QUOTES); ?>')"
                                                    class="admin-btn admin-btn-success"
                                                    title="编辑标签">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="showTagBooks('<?php echo htmlspecialchars($tag['tag_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($tag['book_ids'], ENT_QUOTES); ?>')"
                                                    class="admin-btn admin-btn-info"
                                                    title="查看关联书籍">
                                                    <i class="fas fa-book"></i>
                                                </button>
                                                <form method="POST" class="inline" onsubmit="return confirm('确定要删除标签 \" <?php echo htmlspecialchars($tag['tag_name'], ENT_QUOTES); ?>\" 吗？\n\n这将删除该标签与所有书籍的关联关系！')">
                                                    <input type="hidden" name="form_action" value="delete_tag">
                                                    <input type="hidden" name="tag_name" value="<?php echo htmlspecialchars($tag['tag_name'], ENT_QUOTES); ?>">
                                                    <button type="submit" class="admin-btn admin-btn-danger" title="删除标签">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 分页 -->
                <?php if ($total_pages > 1): ?>
                    <div class="flex justify-center mt-6">
                        <nav class="flex items-center space-x-2">
                            <?php
                            $query_params = ['action' => 'tags'];
                            if (!empty($tag_search)) {
                                $query_params['search'] = $tag_search;
                            }
                            ?>
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <?php $query_params['page'] = $i; ?>
                                <a href="?<?php echo http_build_query($query_params); ?>"
                                    class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-blue-600' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-lg">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($action === 'admin_settings'): ?>
            <!-- 管理员设置 -->
            <div class="admin-card">
                <h2 class="text-2xl font-bold mb-6">管理员设置</h2>

                <form method="POST" class="max-w-md">
                    <input type="hidden" name="form_action" value="update_admin">
                    <input type="hidden" name="admin_id" value="<?php echo $admin_user['id']; ?>">

                    <div class="admin-form-group">
                        <label class="admin-form-label">用户名</label>
                        <input type="text" name="username" class="admin-form-input" value="<?php echo htmlspecialchars($admin_user['username']); ?>" required>
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">邮箱</label>
                        <input type="email" name="email" class="admin-form-input" value="<?php echo htmlspecialchars($admin_user['email']); ?>" required>
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">新密码（留空则不修改）</label>
                        <input type="password" name="password" class="admin-form-input">
                    </div>

                    <button type="submit" class="admin-btn admin-btn-primary">
                        <i class="fas fa-save mr-2"></i> 更新管理员信息
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- 编辑标签模态框 -->
    <div id="editTagModal" class="admin-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:1000;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:20px; border-radius:8px; width:90%; max-width:500px;">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">编辑标签</h3>
                <button onclick="closeModal('editTagModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="form_action" value="edit_tag">
                <input type="hidden" name="old_tag_name" id="editOldTagName">
                <div class="admin-form-group">
                    <label class="admin-form-label">新标签名称</label>
                    <input type="text" name="new_tag_name" id="editNewTagName" class="admin-form-input" required>
                    <p class="text-sm text-gray-500 mt-1">如果新标签名已存在，系统会自动合并</p>
                </div>
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="closeModal('editTagModal')" class="admin-btn admin-btn-danger">取消</button>
                    <button type="submit" class="admin-btn admin-btn-primary">保存</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 合并标签模态框 -->
    <div id="mergeTagModal" class="admin-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:1000;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:20px; border-radius:8px; width:90%; max-width:600px;">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">合并标签</h3>
                <button onclick="closeModal('mergeTagModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="form_action" value="merge_tags">
                <div class="admin-form-group">
                    <label class="admin-form-label">源标签（将被合并的标签）</label>
                    <input type="text" name="source_tag" class="admin-form-input" placeholder="输入要合并的标签名称" required>
                    <p class="text-sm text-gray-500 mt-1">该标签将被删除，其所有关联将转移到目标标签</p>
                </div>
                <div class="admin-form-group">
                    <label class="admin-form-label">目标标签（合并到的标签）</label>
                    <input type="text" name="target_tag" class="admin-form-input" placeholder="输入目标标签名称" required>
                    <p class="text-sm text-gray-500 mt-1">源标签的所有关联将转移到此标签</p>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>警告：</strong>此操作不可逆，请确认源标签和目标标签名称正确！
                </div>
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="closeModal('mergeTagModal')" class="admin-btn admin-btn-danger">取消</button>
                    <button type="submit" class="admin-btn admin-btn-warning" onclick="return confirm('确定要合并标签吗？此操作不可逆！')">确认合并</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 查看标签关联书籍模态框 -->
    <div id="tagBooksModal" class="admin-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:1000;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:20px; border-radius:8px; width:90%; max-width:600px; max-height:80vh; overflow-y:auto;">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold" id="tagBooksTitle">标签关联的书籍</h3>
                <button onclick="closeModal('tagBooksModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="tagBooksList" class="border rounded-lg overflow-hidden">
                <!-- 书籍列表将在这里动态加载 -->
            </div>
            <div class="flex justify-end mt-4">
                <button onclick="closeModal('tagBooksModal')" class="admin-btn admin-btn-primary">关闭</button>
            </div>
        </div>
    </div>

    <!-- 添加用户模态框 -->
    <div id="addUserModal" class="admin-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:1000;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:20px; border-radius:8px; width:90%; max-width:600px;">
            <h3 class="text-lg font-semibold mb-4">添加用户</h3>
            <form method="POST">
                <input type="hidden" name="form_action" value="add_user">

                <div class="admin-form-group">
                    <label class="admin-form-label">用户名</label>
                    <input type="text" name="username" class="admin-form-input" required>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">邮箱</label>
                    <input type="email" name="email" class="admin-form-input" required>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">密码</label>
                    <input type="password" name="password" class="admin-form-input" required>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">角色</label>
                    <select name="role" class="admin-form-select">
                        <option value="user">普通用户</option>
                        <option value="admin">管理员</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('addUserModal')" class="admin-btn admin-btn-danger">取消</button>
                    <button type="submit" class="admin-btn admin-btn-primary">添加</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 编辑用户模态框 -->
    <div id="editUserModal" class="admin-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:1000;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:20px; border-radius:8px; width:90%; max-width:600px;">
            <h3 class="text-lg font-semibold mb-4">编辑用户</h3>
            <form method="POST">
                <input type="hidden" name="form_action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">

                <div class="admin-form-group">
                    <label class="admin-form-label">用户名</label>
                    <input type="text" name="username" id="edit_username" class="admin-form-input" required>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">邮箱</label>
                    <input type="email" name="email" id="edit_email" class="admin-form-input" required>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">密码（留空则不修改）</label>
                    <input type="password" name="password" class="admin-form-input">
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">角色</label>
                    <select name="role" id="edit_role" class="admin-form-select">
                        <option value="user">普通用户</option>
                        <option value="admin">管理员</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('editUserModal')" class="admin-btn admin-btn-danger">取消</button>
                    <button type="submit" class="admin-btn admin-btn-primary">更新</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 添加标签模态框 -->
    <div id="addTagModal" class="admin-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:1000;">
        <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); background:white; padding:20px; border-radius:8px; width:90%; max-width:600px;">
            <h3 class="text-lg font-semibold mb-4">添加标签</h3>
            <form method="POST">
                <input type="hidden" name="form_action" value="add_tag">

                <div class="admin-form-group">
                    <label class="admin-form-label">图书ID</label>
                    <input type="number" name="book_id" class="admin-form-input" required>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">标签名称</label>
                    <input type="text" name="tag_name" class="admin-form-input" required>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeModal('addTagModal')" class="admin-btn admin-btn-danger">取消</button>
                    <button type="submit" class="admin-btn admin-btn-primary">添加</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // 模态框控制
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // 打开编辑标签模态框
        function openEditTagModal(tagName) {
            document.getElementById('editOldTagName').value = tagName;
            document.getElementById('editNewTagName').value = tagName;
            openModal('editTagModal');
        }

        // 显示标签关联的书籍
        function showTagBooks(tagName, bookIds) {
            if (!bookIds || bookIds.trim() === '') {
                alert('该标签暂无关联书籍');
                return;
            }

            // 构建书籍链接
            const ids = bookIds.split(',');
            let bookLinks = '';
            ids.forEach(function(id) {
                if (id.trim()) {
                    bookLinks += '<a href="?action=edit_book&id=' + id.trim() + '" target="_blank" class="block py-2 px-4 hover:bg-gray-100 border-b">书籍 ID: ' + id.trim() + '</a>';
                }
            });

            document.getElementById('tagBooksTitle').textContent = '标签 "' + tagName + '" 关联的书籍';
            document.getElementById('tagBooksList').innerHTML = bookLinks;
            openModal('tagBooksModal');
        }

        // 编辑用户模态框
        function openEditUserModal(id, username, email, role) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            openModal('editUserModal');
        }

        // ESC键关闭模态框
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.admin-modal');
                modals.forEach(modal => {
                    modal.style.display = 'none';
                });
            }
        });

        // 侧边栏切换
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('adminSidebar').classList.toggle('active');
            document.getElementById('overlay').classList.toggle('active');
        });

        document.getElementById('overlay').addEventListener('click', function() {
            document.getElementById('adminSidebar').classList.remove('active');
            document.getElementById('overlay').classList.remove('active');
        });
    </script>

</body>

</html>
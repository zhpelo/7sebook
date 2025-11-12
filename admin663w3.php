<?php
session_start();

require 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die('数据库连接失败');
}

// 检查是否已登录
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    // 已登录，处理管理功能
    $action = $_GET['action'] ?? 'dashboard';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;

    // 处理各种管理操作
    switch ($action) {
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
            // 获取标签列表
            $stmt = $pdo->prepare("
                SELECT bt.*, b.title as book_title, COUNT(bt.book_id) as usage_count
                FROM book_tags bt
                LEFT JOIN books b ON bt.book_id = b.id
                GROUP BY bt.tag_name
                ORDER BY usage_count DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 获取标签总数
            $total_tags = $pdo->query("SELECT COUNT(DISTINCT tag_name) FROM book_tags")->fetchColumn();
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
                    $stmt = $pdo->prepare("
                        INSERT INTO books (title, author, description, category, publisher, page_count, file_name, file_path, file_size, file_format, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$title, $author, $description, $category, $publisher, $page_count, $file_name, $file_path, $file_size, $file_format]);
                    
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
                    $stmt = $pdo->prepare("
                        UPDATE books 
                        SET title = ?, author = ?, description = ?, category = ?, publisher = ?, page_count = ?, 
                            file_name = ?, file_path = ?, file_size = ?, file_format = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$title, $author, $description, $category, $publisher, $page_count, $file_name, $file_path, $file_size, $file_format, $book_id]);
                    
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
                $tag_id = intval($_POST['tag_id'] ?? 0);
                if ($tag_id) {
                    $stmt = $pdo->prepare("DELETE FROM book_tags WHERE id = ?");
                    $stmt->execute([$tag_id]);
                    $message = '标签删除成功';
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
    function build_page_url($action, $new_page) {
        $params = ['action' => $action, 'page' => $new_page];
        return '?' . http_build_query($params);
    }
} else {
    // 未登录，处理登录功能
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if ($username && $password) {
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ? AND role = 'admin'");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $user['username'];
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $login_error = '用户名或密码错误';
            }
        } else {
            $login_error = '请填写用户名和密码';
        }
    }
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
    <style>
        .admin-sidebar {
            width: 250px;
            position: fixed;
            height: 100vh;
            background: #2d3748;
            color: white;
            overflow-y: auto;
        }
        
        .admin-content {
            margin-left: 250px;
            padding: 20px;
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
    </style>
</head>
<body class="bg-gray-100">
    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
        <!-- 侧边栏 -->
        <div class="admin-sidebar">
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
                <a href="?logout=1" class="block py-3 px-4 rounded hover:bg-gray-700 mt-4">
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
                        <button onclick="openModal('addTagModal')" class="admin-btn admin-btn-primary">
                            <i class="fas fa-plus mr-2"></i> 添加标签
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>标签名称</th>
                                    <th>关联图书</th>
                                    <th>使用次数</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tags as $tag): ?>
                                <tr>
                                    <td><?php echo $tag['id']; ?></td>
                                    <td><?php echo htmlspecialchars($tag['tag_name']); ?></td>
                                    <td><?php echo htmlspecialchars($tag['book_title'] ?? '无'); ?></td>
                                    <td><?php echo $tag['usage_count']; ?></td>
                                    <td>
                                        <form method="POST" class="inline" onsubmit="return confirm('确定要删除此标签吗？')">
                                            <input type="hidden" name="form_action" value="delete_tag">
                                            <input type="hidden" name="tag_id" value="<?php echo $tag['id']; ?>">
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
                                <a href="<?php echo build_page_url('tags', $i); ?>" class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-blue-600' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-lg">
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
        </script>
    <?php else: ?>
        <!-- 登录页面 -->
        <div class="login-container">
            <div class="login-card">
                <div class="text-center mb-8">
                    <i class="fas fa-book text-5xl text-blue-600 mb-4"></i>
                    <h1 class="text-2xl font-bold text-gray-800">管理员登录</h1>
                </div>
                
                <?php if (isset($login_error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($login_error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="login" value="1">
                    
                    <div class="admin-form-group">
                        <label class="admin-form-label">用户名</label>
                        <input type="text" name="username" class="admin-form-input" required>
                    </div>
                    
                    <div class="admin-form-group">
                        <label class="admin-form-label">密码</label>
                        <input type="password" name="password" class="admin-form-input" required>
                    </div>
                    
                    <button type="submit" class="w-full admin-btn admin-btn-primary py-3">
                        <i class="fas fa-sign-in-alt mr-2"></i> 登录
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php
    // 处理退出登录
    if (isset($_GET['logout'])) {
        session_destroy();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    ?>
</body>
</html>
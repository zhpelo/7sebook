<?php
// 管理员后台页面
session_start();
require 'common.php';

// 只允许管理员访问
if (!is_admin_logged_in()) {
    header('Location: index.php');
    exit;
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
        // 获取搜索关键词
        $user_search = trim($_GET['search'] ?? '');
        
        // 构建查询条件
        $where_clause = '';
        $params = [];
        if (!empty($user_search)) {
            $where_clause = 'WHERE username LIKE :search OR email LIKE :search';
            $params[':search'] = '%' . $user_search . '%';
        }
        
        // 获取用户列表
        $sql = "SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取用户总数
        $count_sql = "SELECT COUNT(*) FROM users $where_clause";
        $count_stmt = $pdo->prepare($count_sql);
        foreach ($params as $key => $value) {
            $count_stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $count_stmt->execute();
        $total_users = $count_stmt->fetchColumn();
        $total_pages = ceil($total_users / $limit);
        break;

    case 'books':
        // 获取搜索关键词
        $book_search = trim($_GET['search'] ?? '');
        
        // 构建查询条件
        $where_clause = '';
        $params = [];
        if (!empty($book_search)) {
            $where_clause = 'WHERE b.title LIKE :search OR b.author LIKE :search';
            $params[':search'] = '%' . $book_search . '%';
        }
        
        // 获取电子书列表
        $sql = "
            SELECT b.*, 
                   GROUP_CONCAT(bt.tag_name) as tags
            FROM books b
            LEFT JOIN book_tags bt ON b.id = bt.book_id
            $where_clause
            GROUP BY b.id
            ORDER BY b.created_at DESC 
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取电子书总数
        $count_sql = "SELECT COUNT(DISTINCT b.id) FROM books b $where_clause";
        $count_stmt = $pdo->prepare($count_sql);
        foreach ($params as $key => $value) {
            $count_stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $count_stmt->execute();
        $total_books = $count_stmt->fetchColumn();
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

        // 获取最近28天的下载趋势数据
        $stmt = $pdo->prepare("
            SELECT 
                DATE(download_time) as date,
                COUNT(*) as download_count
            FROM download_logs 
            WHERE download_time >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)
            GROUP BY DATE(download_time)
            ORDER BY DATE(download_time)
        ");
        $stmt->execute();
        $download_trend_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 构建完整的28天数据
        $daily_download_counts = [];
        $download_dates = [];
        for ($i = 27; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $download_dates[] = $date;
            $daily_download_counts[$date] = 0;
        }

        // 填充实际数据
        foreach ($download_trend_data as $data) {
            $daily_download_counts[$data['date']] = (int)$data['download_count'];
        }

        // 获取累计下载最高的10本书
        $stmt = $pdo->prepare("
            SELECT 
                b.id,
                b.title,
                b.author,
                COUNT(dl.id) as download_count
            FROM books b
            LEFT JOIN download_logs dl ON b.id = dl.book_id
            GROUP BY b.id, b.title, b.author
            ORDER BY download_count DESC
            LIMIT 10
        ");
        $stmt->execute();
        $top_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                header('Location: ?action=books&error=' . urlencode('电子书不存在'));
                exit;
            }
            
            // 如果是POST请求，处理更新电子书
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                    $current_time = get_current_time();
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
                        $current_time = get_current_time();
                        foreach ($tag_list as $tag) {
                            if ($tag) {
                                $tag_stmt = $pdo->prepare("INSERT INTO book_tags (book_id, tag_name, created_at) VALUES (?, ?, ?)");
                                $tag_stmt->execute([$book_id, $tag, $current_time]);
                            }
                        }
                    }

                    header('Location: ?action=books&message=' . urlencode('电子书信息更新成功'));
                    exit;
                } else {
                    $error = '请填写必填字段（标题、作者）';
                }
            }
        } else {
            header('Location: ?action=books&error=' . urlencode('未指定电子书ID'));
            exit;
        }
        break;

    case 'add_user':
        // 如果是POST请求，处理添加用户
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'user';

            if ($username && $password && $email) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $password_hash, $email, $role]);
                header('Location: ?action=users&message=' . urlencode('用户添加成功'));
                exit;
            } else {
                $error = '请填写完整信息';
            }
        }
        break;

    case 'edit_user':
        $user_id = intval($_GET['id'] ?? 0);
        if ($user_id) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$edit_user) {
                header('Location: ?action=users&error=' . urlencode('用户不存在'));
                exit;
            }
            
            // 如果是POST请求，处理更新用户
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $role = $_POST['role'] ?? 'user';
                $new_password = $_POST['password'] ?? '';

                if ($username && $email) {
                    if ($new_password) {
                        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, password_hash = ? WHERE id = ?");
                        $stmt->execute([$username, $email, $role, $password_hash, $user_id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
                        $stmt->execute([$username, $email, $role, $user_id]);
                    }
                    header('Location: ?action=users&message=' . urlencode('用户信息更新成功'));
                    exit;
                } else {
                    $error = '请填写完整信息';
                }
            }
        } else {
            header('Location: ?action=users&error=' . urlencode('未指定用户ID'));
            exit;
        }
        break;

    case 'delete_user':
        $user_id = intval($_GET['id'] ?? 0);
        $confirm = $_GET['confirm'] ?? '';
        if ($user_id && $confirm === 'yes') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            header('Location: ?action=users&message=' . urlencode('用户删除成功'));
            exit;
        } elseif ($user_id) {
            // 显示确认页面
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $delete_user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            header('Location: ?action=users&error=' . urlencode('未指定用户ID'));
            exit;
        }
        break;

    case 'batch_delete_users':
        $user_ids = $_GET['ids'] ?? '';
        $confirm = $_GET['confirm'] ?? '';
        if ($user_ids && $confirm === 'yes') {
            $ids = explode(',', $user_ids);
            $ids = array_filter(array_map('intval', $ids));
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                header('Location: ?action=users&message=' . urlencode('已成功删除 ' . count($ids) . ' 个用户'));
                exit;
            }
        }
        header('Location: ?action=users');
        exit;

    case 'add_book':
        // 如果是POST请求，处理添加电子书
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                $current_time = get_current_time();
                $stmt = $pdo->prepare("
                    INSERT INTO books (title, author, description, category, publisher, page_count, file_name, file_path, file_size, file_format, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $author, $description, $category, $publisher, $page_count, $file_name, $file_path, $file_size, $file_format, $current_time]);

                $book_id = $pdo->lastInsertId();

                if ($tags) {
                    $tag_list = array_filter(array_map('trim', explode(',', $tags)));
                    $current_time = get_current_time();
                    foreach ($tag_list as $tag) {
                        if ($tag) {
                            $tag_stmt = $pdo->prepare("INSERT INTO book_tags (book_id, tag_name, created_at) VALUES (?, ?, ?)");
                            $tag_stmt->execute([$book_id, $tag, $current_time]);
                        }
                    }
                }

                header('Location: ?action=books&message=' . urlencode('电子书添加成功'));
                exit;
            } else {
                $error = '请填写必填字段（标题、作者）';
            }
        }
        break;

    case 'delete_book':
        $book_id = intval($_GET['id'] ?? 0);
        $confirm = $_GET['confirm'] ?? '';
        if ($book_id && $confirm === 'yes') {
            $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            header('Location: ?action=books&message=' . urlencode('电子书删除成功'));
            exit;
        } elseif ($book_id) {
            $stmt = $pdo->prepare("SELECT id, title FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $delete_book = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            header('Location: ?action=books&error=' . urlencode('未指定电子书ID'));
            exit;
        }
        break;

    case 'batch_delete_books':
        $book_ids = $_GET['ids'] ?? '';
        $confirm = $_GET['confirm'] ?? '';
        if ($book_ids && $confirm === 'yes') {
            $ids = explode(',', $book_ids);
            $ids = array_filter(array_map('intval', $ids));
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM books WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                header('Location: ?action=books&message=' . urlencode('已成功删除 ' . count($ids) . ' 本电子书'));
                exit;
            }
        }
        header('Location: ?action=books');
        exit;

    case 'add_tag':
        // 如果是POST请求，处理添加标签
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tag_name = trim($_POST['tag_name'] ?? '');
            $auto_search = isset($_POST['auto_search']) && $_POST['auto_search'] == '1';
            $book_id = intval($_POST['book_id'] ?? 0);

            if ($tag_name) {
                $current_time = get_current_time();
                $added_count = 0;

                if ($auto_search) {
                    // 自动搜索模式：查找标题或作者包含标签名称的书籍
                    $search_term = '%' . $tag_name . '%';
                    $stmt = $pdo->prepare("
                        SELECT DISTINCT id 
                        FROM books 
                        WHERE (title LIKE ? OR author LIKE ?)
                    ");
                    $stmt->execute([$search_term, $search_term]);
                    $matching_books = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($matching_books)) {
                        // 为每个匹配的书籍添加标签（如果标签不存在）
                        foreach ($matching_books as $bid) {
                            // 检查该书籍是否已有此标签
                            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM book_tags WHERE book_id = ? AND tag_name = ?");
                            $check_stmt->execute([$bid, $tag_name]);
                            $exists = $check_stmt->fetchColumn();

                            if (!$exists) {
                                $insert_stmt = $pdo->prepare("INSERT INTO book_tags (book_id, tag_name, created_at) VALUES (?, ?, ?)");
                                $insert_stmt->execute([$bid, $tag_name, $current_time]);
                                $added_count++;
                            }
                        }
                        
                        if ($added_count > 0) {
                            header('Location: ?action=tags&message=' . urlencode("标签添加成功！已为 {$added_count} 本匹配的书籍添加了标签"));
                        } else {
                            header('Location: ?action=tags&message=' . urlencode('未找到新的匹配书籍，所有匹配的书籍已包含此标签'));
                        }
                    } else {
                        header('Location: ?action=tags&message=' . urlencode('未找到匹配的书籍'));
                    }
                    exit;
                } else {
                    // 手动模式：为指定图书添加标签
                    if ($book_id) {
                        // 检查该书籍是否已有此标签
                        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM book_tags WHERE book_id = ? AND tag_name = ?");
                        $check_stmt->execute([$book_id, $tag_name]);
                        $exists = $check_stmt->fetchColumn();

                        if (!$exists) {
                            $stmt = $pdo->prepare("INSERT INTO book_tags (book_id, tag_name, created_at) VALUES (?, ?, ?)");
                            $stmt->execute([$book_id, $tag_name, $current_time]);
                            header('Location: ?action=tags&message=' . urlencode('标签添加成功'));
                        } else {
                            header('Location: ?action=tags&message=' . urlencode('该书籍已包含此标签'));
                        }
                        exit;
                    } else {
                        $error = '请启用自动搜索功能或输入图书ID';
                    }
                }
            } else {
                $error = '请输入标签名称';
            }
        }
        break;

    case 'delete_tag':
        $tag_name = trim($_GET['tag'] ?? '');
        $confirm = $_GET['confirm'] ?? '';
        if ($tag_name && $confirm === 'yes') {
            $stmt = $pdo->prepare("DELETE FROM book_tags WHERE tag_name = ?");
            $stmt->execute([$tag_name]);
            header('Location: ?action=tags&message=' . urlencode('标签删除成功'));
            exit;
        }
        header('Location: ?action=tags');
        exit;

    case 'edit_tag':
        $old_tag_name = trim($_GET['tag'] ?? '');
        if (!$old_tag_name) {
            header('Location: ?action=tags&error=' . urlencode('未指定标签名称'));
            exit;
        }
        
        // 如果是POST请求，处理编辑标签
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_tag_name = trim($_POST['new_tag_name'] ?? '');

            if ($new_tag_name && $old_tag_name !== $new_tag_name) {
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM book_tags WHERE tag_name = ?");
                $check_stmt->execute([$new_tag_name]);
                $exists = $check_stmt->fetchColumn();

                if ($exists > 0) {
                    $update_stmt = $pdo->prepare("UPDATE book_tags SET tag_name = ? WHERE tag_name = ?");
                    $update_stmt->execute([$new_tag_name, $old_tag_name]);

                    $delete_dup_stmt = $pdo->prepare("
                        DELETE t1 FROM book_tags t1
                        INNER JOIN book_tags t2 
                        WHERE t1.id > t2.id 
                        AND t1.tag_name = t2.tag_name 
                        AND t1.book_id = t2.book_id
                    ");
                    $delete_dup_stmt->execute();

                    header('Location: ?action=tags&message=' . urlencode('标签已重命名并合并'));
                    exit;
                } else {
                    $update_stmt = $pdo->prepare("UPDATE book_tags SET tag_name = ? WHERE tag_name = ?");
                    $update_stmt->execute([$new_tag_name, $old_tag_name]);
                    header('Location: ?action=tags&message=' . urlencode('标签重命名成功'));
                    exit;
                }
            } else {
                $error = '标签名称不能为空或相同';
            }
        }
        break;

    case 'merge_tags':
        // 如果是POST请求，处理合并标签
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $source_tag = trim($_POST['source_tag'] ?? '');
            $target_tag = trim($_POST['target_tag'] ?? '');

            if ($source_tag && $target_tag && $source_tag !== $target_tag) {
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM book_tags WHERE tag_name = ?");
                $check_stmt->execute([$target_tag]);
                $target_exists = $check_stmt->fetchColumn();

                if ($target_exists == 0) {
                    $update_stmt = $pdo->prepare("UPDATE book_tags SET tag_name = ? WHERE tag_name = ?");
                    $update_stmt->execute([$target_tag, $source_tag]);
                    header('Location: ?action=tags&message=' . urlencode('标签合并成功（源标签已重命名为目标标签）'));
                    exit;
                } else {
                    $current_time = get_current_time();
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

                    $delete_stmt = $pdo->prepare("DELETE FROM book_tags WHERE tag_name = ?");
                    $delete_stmt->execute([$source_tag]);

                    header('Location: ?action=tags&message=' . urlencode('标签合并成功'));
                    exit;
                }
            } else {
                $error = '请选择要合并的标签，且源标签和目标标签不能相同';
            }
        }
        break;

    case 'view_tag_books':
        $tag_name = trim($_GET['tag'] ?? '');
        if ($tag_name) {
            $stmt = $pdo->prepare("
                SELECT DISTINCT b.id, b.title, b.author
                FROM books b
                INNER JOIN book_tags bt ON b.id = bt.book_id
                WHERE bt.tag_name = ?
                ORDER BY b.id
            ");
            $stmt->execute([$tag_name]);
            $tag_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            header('Location: ?action=tags&error=' . urlencode('未指定标签名称'));
            exit;
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

// 处理管理员设置POST提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'admin_settings') {
    $admin_id = intval($_POST['admin_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($admin_id && $username && $email) {
        if ($password) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$username, $email, $password_hash, $admin_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->execute([$username, $email, $admin_id]);
        }
        $_SESSION['admin_username'] = $username;
        header('Location: ?action=admin_settings&message=' . urlencode('管理员信息更新成功'));
        exit;
    } else {
        $error = '请填写用户名和邮箱';
    }
}

// 从URL参数获取消息
$message = $_GET['message'] ?? null;
$error = $_GET['error'] ?? null;

// 使用 common.php 中的函数
// build_admin_page_url 已在 common.php 中定义
function build_page_url($action, $new_page)
{
    return build_admin_page_url($action, $new_page);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] ? '七秒电子书后台管理' : '管理员登录'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    transitionProperty: {
                        'width': 'width',
                        'transform': 'transform',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50">
    <!-- 移动端遮罩层 -->
    <a href="#" data-action="toggle-sidebar" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden transition-opacity duration-300" id="overlay"></a>

    <!-- 侧边栏 -->
    <aside id="adminSidebar" class="fixed top-0 left-0 h-screen w-64 bg-gray-800 text-white z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out overflow-y-auto">
        <div class="p-6 border-b border-gray-700">
            <h1 class="text-xl font-bold flex items-center">
                <i class="fas fa-book mr-2 text-blue-400"></i>
                <span class="hidden sm:inline">七秒后台</span>
                <span class="sm:hidden">后台</span>
            </h1>
        </div>

        <nav class="p-4 space-y-1">
            <a href="?action=dashboard" class="flex items-center py-3 px-4 rounded-lg transition-colors <?php echo $action === 'dashboard' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                <i class="fas fa-tachometer-alt w-5 mr-3"></i>
                <span>仪表盘</span>
            </a>
            <a href="?action=users" class="flex items-center py-3 px-4 rounded-lg transition-colors <?php echo $action === 'users' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                <i class="fas fa-users w-5 mr-3"></i>
                <span>用户管理</span>
            </a>
            <a href="?action=books" class="flex items-center py-3 px-4 rounded-lg transition-colors <?php echo $action === 'books' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                <i class="fas fa-book w-5 mr-3"></i>
                <span>书籍管理</span>
            </a>
            <a href="?action=downloads" class="flex items-center py-3 px-4 rounded-lg transition-colors <?php echo $action === 'downloads' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                <i class="fas fa-download w-5 mr-3"></i>
                <span>下载记录</span>
            </a>
            <a href="?action=tags" class="flex items-center py-3 px-4 rounded-lg transition-colors <?php echo $action === 'tags' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                <i class="fas fa-tags w-5 mr-3"></i>
                <span>标签管理</span>
            </a>
            <a href="?action=admin_settings" class="flex items-center py-3 px-4 rounded-lg transition-colors <?php echo $action === 'admin_settings' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                <i class="fas fa-cog w-5 mr-3"></i>
                <span>管理员设置</span>
            </a>
            <a href="?action=logout" class="flex items-center py-3 px-4 rounded-lg transition-colors text-gray-300 hover:bg-red-600 hover:text-white mt-4">
                <i class="fas fa-sign-out-alt w-5 mr-3"></i>
                <span>退出登录</span>
            </a>
        </nav>
    </aside>

    <!-- 移动端顶部栏 -->
    <header class="lg:hidden fixed top-0 left-0 right-0 bg-white shadow-md z-30 px-4 py-3 flex items-center justify-between">
        <a href="#" data-action="toggle-sidebar" class="p-2 rounded-lg hover:bg-gray-100">
            <i class="fas fa-bars text-gray-700"></i>
        </a>
        <h1 class="text-lg font-bold text-gray-800">七秒后台</h1>
        <div class="w-10"></div>
    </header>

    <!-- 主内容区域 -->
    <main class="lg:ml-64 pt-16 lg:pt-0 min-h-screen">
        <div class="p-4 lg:p-6">
            <!-- 消息提示 -->
        <?php if (isset($message)): ?>
                <div class="mb-4 bg-green-50 border-l-4 border-green-400 text-green-700 p-4 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
                <div class="mb-4 bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
            </div>
        <?php endif; ?>

        <?php if ($action === 'dashboard'): ?>
            <!-- 仪表盘 -->
                <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">系统概览</h2>

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
                                    <p class="font-semibold text-gray-800">MySQL <?php echo $pdo->getAttribute(PDO::ATTR_SERVER_VERSION); ?></p>
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
                                    <p class="font-semibold text-gray-800"><?php echo get_current_time(); ?></p>
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

        <?php elseif ($action === 'users'): ?>
            <!-- 用户管理 -->
                <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <h2 class="text-2xl font-bold text-gray-800">用户管理</h2>
                        <a href="?action=add_user" class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-plus mr-2"></i> 添加用户
                    </a>
                </div>

                    <!-- 搜索和批量操作栏 -->
                    <div class="mb-4 flex flex-col sm:flex-row gap-3">
                        <form method="GET" class="flex-1 flex gap-2">
                            <input type="hidden" name="action" value="users">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($user_search ?? ''); ?>" 
                                placeholder="搜索用户名或邮箱..." 
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                <i class="fas fa-search"></i>
                    </button>
                            <?php if (!empty($user_search)): ?>
                                <a href="?action=users" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </form>
                        <div id="batchActions" class="hidden flex gap-2">
                            <a href="#" data-action="batch-delete-users" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-trash mr-2"></i> 批量删除
                            </a>
                            <a href="#" data-action="clear-selection" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                                取消选择
                            </a>
                        </div>
                </div>

                    <!-- 表格 -->
                <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left">
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用户名</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">邮箱</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">角色</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">创建时间</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                            <?php echo !empty($user_search) ? '未找到匹配的用户' : '暂无用户'; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3">
                                                <input type="checkbox" name="user_ids[]" value="<?php echo $user['id']; ?>" 
                                                    class="user-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" 
                                                    onchange="updateBatchActions()">
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $user['id']; ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                                    <?php echo $user['role'] === 'admin' ? '管理员' : '普通用户'; ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell"><?php echo $user['created_at']; ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center gap-2">
                                                    <a href="?action=edit_user&id=<?php echo $user['id']; ?>" 
                                                        class="text-green-600 hover:text-green-900 p-1 inline-flex items-center justify-center cursor-pointer rounded hover:bg-green-50 transition-colors"
                                                        title="编辑用户">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?action=delete_user&id=<?php echo $user['id']; ?>" 
                                                        class="text-red-600 hover:text-red-900 p-1 inline-flex items-center justify-center cursor-pointer rounded hover:bg-red-50 transition-colors"
                                                        title="删除用户"
                                                        onclick="return confirm('确定要删除此用户吗？');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
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
                        <div class="flex flex-col sm:flex-row justify-between items-center mt-6 gap-4">
                            <div class="text-sm text-gray-700">
                                共 <?php echo $total_users; ?> 条记录，第 <?php echo $page; ?> / <?php echo $total_pages; ?> 页
                            </div>
                        <nav class="flex items-center space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="<?php echo build_page_url('users', $page - 1) . (!empty($user_search) ? '&search=' . urlencode($user_search) : ''); ?>" 
                                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                        上一页
                                    </a>
                                <?php endif; ?>
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="<?php echo build_page_url('users', $i) . (!empty($user_search) ? '&search=' . urlencode($user_search) : ''); ?>" 
                                        class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-blue-600' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-lg">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="<?php echo build_page_url('users', $page + 1) . (!empty($user_search) ? '&search=' . urlencode($user_search) : ''); ?>" 
                                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                        下一页
                                    </a>
                                <?php endif; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($action === 'add_user' && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <!-- 添加用户页面 -->
            <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800">添加用户</h2>
                    <a href="?action=users" class="w-full sm:w-auto px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-arrow-left mr-2"></i> 返回列表
                    </a>
                </div>
                <form method="POST" action="?action=add_user" class="max-w-md">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">用户名 <span class="text-red-500">*</span></label>
                            <input type="text" name="username" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">邮箱 <span class="text-red-500">*</span></label>
                            <input type="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">密码 <span class="text-red-500">*</span></label>
                            <input type="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">角色</label>
                            <select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="user">普通用户</option>
                                <option value="admin">管理员</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <a href="?action=users" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">取消</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">添加</button>
                    </div>
                </form>
            </div>

        <?php elseif ($action === 'edit_user' && isset($edit_user) && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <!-- 编辑用户页面 -->
            <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800">编辑用户</h2>
                    <a href="?action=users" class="w-full sm:w-auto px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-arrow-left mr-2"></i> 返回列表
                    </a>
                </div>
                <form method="POST" action="?action=edit_user&id=<?php echo $edit_user['id']; ?>" class="max-w-md">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">用户名 <span class="text-red-500">*</span></label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($edit_user['username']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">邮箱 <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">密码（留空则不修改）</label>
                            <input type="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">角色</label>
                            <select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="user" <?php echo $edit_user['role'] === 'user' ? 'selected' : ''; ?>>普通用户</option>
                                <option value="admin" <?php echo $edit_user['role'] === 'admin' ? 'selected' : ''; ?>>管理员</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <a href="?action=users" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">取消</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">更新</button>
                    </div>
                </form>
            </div>

        <?php elseif ($action === 'delete_user' && isset($delete_user) && !isset($_GET['confirm'])): ?>
            <!-- 删除用户确认页面 -->
            <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6 max-w-md mx-auto">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">确认删除用户</h2>
                <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded mb-4">
                    <p class="font-semibold">警告：此操作不可恢复！</p>
                    <p class="text-sm mt-1">确定要删除用户 "<?php echo htmlspecialchars($delete_user['username']); ?>" 吗？</p>
                </div>
                <div class="flex justify-end gap-2">
                    <a href="?action=users" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">取消</a>
                    <a href="?action=delete_user&id=<?php echo $delete_user['id']; ?>&confirm=yes" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">确认删除</a>
                </div>
            </div>

        <?php elseif ($action === 'delete_book' && isset($delete_book) && !isset($_GET['confirm'])): ?>
            <!-- 删除电子书确认页面 -->
            <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6 max-w-md mx-auto">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">确认删除电子书</h2>
                <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded mb-4">
                    <p class="font-semibold">警告：此操作不可恢复！</p>
                    <p class="text-sm mt-1">确定要删除电子书 "<?php echo htmlspecialchars($delete_book['title']); ?>" 吗？</p>
                </div>
                <div class="flex justify-end gap-2">
                    <a href="?action=books" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">取消</a>
                    <a href="?action=delete_book&id=<?php echo $delete_book['id']; ?>&confirm=yes" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">确认删除</a>
                </div>
            </div>

        <?php elseif ($action === 'edit_book' && isset($book)): ?>
            <!-- 编辑电子书页面 -->
            <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800">编辑电子书</h2>
                    <a href="?action=books" class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-arrow-left mr-2"></i> 返回列表
                    </a>
                </div>

                <form method="POST" class="max-w-3xl" action="?action=edit_book&id=<?php echo $book['id']; ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">标题 <span class="text-red-500">*</span></label>
                            <input type="text" name="title" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                    </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">作者 <span class="text-red-500">*</span></label>
                            <input type="text" name="author" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">简介</label>
                        <textarea name="description" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="4"><?php echo htmlspecialchars($book['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">类别</label>
                            <input type="text" name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['category'] ?? ''); ?>">
                    </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">出版社</label>
                            <input type="text" name="publisher" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['publisher'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">页数</label>
                            <input type="number" name="page_count" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo $book['page_count'] ?? 0; ?>">
                    </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">文件格式</label>
                            <input type="text" name="file_format" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['file_format'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">文件名</label>
                            <input type="text" name="file_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['file_name'] ?? ''); ?>">
                    </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">文件大小</label>
                            <input type="text" name="file_size" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['file_size'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">文件路径</label>
                        <input type="text" name="file_path" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['file_path'] ?? ''); ?>">
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">标签（逗号分隔）</label>
                        <input type="text" name="tags" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($book['tags'] ?? ''); ?>" placeholder="标签1,标签2,标签3">
                        <p class="text-xs text-gray-500 mt-1">多个标签请用逗号分隔</p>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-2">
                        <a href="?action=books" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors text-center">取消</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">更新电子书</button>
                    </div>
                </form>
            </div>

        <?php elseif ($action === 'add_book' && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <!-- 添加电子书页面 -->
            <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800">添加电子书</h2>
                    <a href="?action=books" class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-arrow-left mr-2"></i> 返回列表
                    </a>
                </div>

                <form method="POST" class="max-w-3xl" action="?action=add_book">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">标题 <span class="text-red-500">*</span></label>
                            <input type="text" name="title" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">作者 <span class="text-red-500">*</span></label>
                            <input type="text" name="author" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">简介</label>
                        <textarea name="description" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" rows="4"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">类别</label>
                            <input type="text" name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">出版社</label>
                            <input type="text" name="publisher" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">页数</label>
                            <input type="number" name="page_count" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">文件格式</label>
                            <input type="text" name="file_format" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">文件名</label>
                            <input type="text" name="file_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">文件大小</label>
                            <input type="text" name="file_size" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">文件路径</label>
                        <input type="text" name="file_path" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">标签（逗号分隔）</label>
                        <input type="text" name="tags" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="标签1,标签2,标签3">
                        <p class="text-xs text-gray-500 mt-1">多个标签请用逗号分隔</p>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-end gap-2">
                        <a href="?action=books" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors text-center">取消</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">添加电子书</button>
                    </div>
                </form>
            </div>

        <?php elseif ($action === 'books'): ?>
            <!-- 书籍管理 -->
                <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                        <h2 class="text-2xl font-bold text-gray-800">书籍管理</h2>
                        <a href="?action=add_book" class="w-full sm:w-auto px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-plus mr-2"></i> 添加电子书
                    </a>
                </div>

                    <!-- 搜索和批量操作栏 -->
                    <div class="mb-4 flex flex-col sm:flex-row gap-3">
                        <form method="GET" class="flex-1 flex gap-2">
                            <input type="hidden" name="action" value="books">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($book_search ?? ''); ?>" 
                                placeholder="搜索书名或作者..." 
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (!empty($book_search)): ?>
                                <a href="?action=books" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </form>
                        <div id="batchActions" class="hidden flex gap-2">
                            <a href="#" data-action="batch-delete-books" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                <i class="fas fa-trash mr-2"></i> 批量删除
                            </a>
                            <a href="#" data-action="clear-selection" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                                取消选择
                            </a>
                        </div>
                    </div>

                    <!-- 表格 -->
                <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left">
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">标题</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">作者</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">类别</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">格式</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">大小</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($books)): ?>
                                    <tr>
                                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                            <?php echo !empty($book_search) ? '未找到匹配的电子书' : '暂无电子书'; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($books as $book): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3">
                                                <input type="checkbox" name="book_ids[]" value="<?php echo $book['id']; ?>" 
                                                    class="book-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" 
                                                    onchange="updateBatchActions()">
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $book['id']; ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($book['title']); ?>">
                                                    <?php echo htmlspecialchars($book['title']); ?>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                                <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($book['author']); ?>">
                                                    <?php echo htmlspecialchars($book['author']); ?>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">
                                                <?php echo htmlspecialchars($book['category'] ?? '-'); ?>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    <?php echo strtoupper(htmlspecialchars($book['file_format'])); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 hidden lg:table-cell">
                                                <?php echo format_file_size($book['file_size'] ?? '-'); ?>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center gap-2">
                                                    <a href="?action=edit_book&id=<?php echo $book['id']; ?>" 
                                                        class="text-green-600 hover:text-green-900 p-1" title="编辑">
                                                        <i class="fas fa-edit"></i>
                                        </a>
                                                    <a href="?action=delete_book&id=<?php echo $book['id']; ?>" 
                                                        class="text-red-600 hover:text-red-900 p-1 inline-flex items-center justify-center cursor-pointer rounded hover:bg-red-50 transition-colors"
                                                        title="删除"
                                                        onclick="return confirm('确定要删除此电子书吗？');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
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
                        <div class="flex flex-col sm:flex-row justify-between items-center mt-6 gap-4">
                            <div class="text-sm text-gray-700">
                                共 <?php echo $total_books; ?> 条记录，第 <?php echo $page; ?> / <?php echo $total_pages; ?> 页
                            </div>
                        <nav class="flex items-center space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="<?php echo build_page_url('books', $page - 1) . (!empty($book_search) ? '&search=' . urlencode($book_search) : ''); ?>" 
                                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                        上一页
                                    </a>
                                <?php endif; ?>
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="<?php echo build_page_url('books', $i) . (!empty($book_search) ? '&search=' . urlencode($book_search) : ''); ?>" 
                                        class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-blue-600' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-lg">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="<?php echo build_page_url('books', $page + 1) . (!empty($book_search) ? '&search=' . urlencode($book_search) : ''); ?>" 
                                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                        下一页
                                    </a>
                                <?php endif; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($action === 'downloads'): ?>
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
                        <div class="flex flex-col sm:flex-row justify-between items-center mt-6 gap-4">
                            <div class="text-sm text-gray-700">
                                共 <?php echo $total_downloads; ?> 条记录，第 <?php echo $page; ?> / <?php echo $total_pages; ?> 页
                            </div>
                        <nav class="flex items-center space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="<?php echo build_page_url('downloads', $page - 1); ?>" 
                                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                        上一页
                                    </a>
                                <?php endif; ?>
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="<?php echo build_page_url('downloads', $i); ?>" 
                                        class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-blue-600' : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-lg">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                                <?php if ($page < $total_pages): ?>
                                    <a href="<?php echo build_page_url('downloads', $page + 1); ?>" 
                                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                        下一页
                                    </a>
                                <?php endif; ?>
                        </nav>
                    </div>
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
                                },
                                interaction: {
                                    mode: 'nearest',
                                    axis: 'x',
                                    intersect: false
                                }
                            }
                        });
                    }
                });
            </script>

        <?php elseif ($action === 'tags'): ?>
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

        <?php elseif ($action === 'add_tag' && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <!-- 添加标签页面 -->
            <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">添加标签</h2>
                        <p class="text-sm text-gray-500 mt-1">为书籍添加新标签</p>
                    </div>
                    <a href="?action=tags" class="w-full sm:w-auto px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-arrow-left mr-2"></i> 返回列表
                    </a>
                </div>
                <form method="POST" action="?action=add_tag" class="max-w-2xl" id="addTagForm">
                    <div class="space-y-5 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">标签名称 <span class="text-red-500">*</span></label>
                            <input type="text" name="tag_name" id="tag_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="输入标签名称，如：科幻、历史、文学等" required>
                            <p class="text-xs text-gray-500 mt-1">标签名称将用于分类和搜索书籍</p>
                        </div>

                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="auto_search" id="auto_search" value="1" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-2 text-sm font-medium text-gray-700">启用自动搜索添加功能</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-2 ml-6">
                                <i class="fas fa-info-circle mr-1"></i>
                                勾选后，系统将自动搜索书籍表中标题或作者包含此标签名称的书籍，并全部加入此标签
                            </p>
                        </div>

                        <div id="book_id_field">
                            <label class="block text-sm font-medium text-gray-700 mb-1">图书ID</label>
                            <input type="number" name="book_id" id="book_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="如果不使用自动搜索，请输入图书ID">
                            <p class="text-xs text-gray-500 mt-1">手动指定要添加标签的图书ID（仅在未启用自动搜索时使用）</p>
                        </div>
                    </div>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-lightbulb text-blue-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>提示：</strong>启用自动搜索功能后，系统会在书籍的标题和作者字段中搜索包含标签名称的内容。例如，标签"科幻"会匹配所有标题或作者中包含"科幻"二字的书籍。
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <a href="?action=tags" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">取消</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i> 添加标签
                        </button>
                    </div>
                </form>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const autoSearchCheckbox = document.getElementById('auto_search');
                        const bookIdField = document.getElementById('book_id_field');
                        const bookIdInput = document.getElementById('book_id');
                        const tagNameInput = document.getElementById('tag_name');
                        const form = document.getElementById('addTagForm');

                        // 切换自动搜索功能时显示/隐藏图书ID字段
                        autoSearchCheckbox.addEventListener('change', function() {
                            if (this.checked) {
                                bookIdField.style.display = 'none';
                                bookIdInput.removeAttribute('required');
                                bookIdInput.value = '';
                            } else {
                                bookIdField.style.display = 'block';
                                bookIdInput.setAttribute('required', 'required');
                            }
                        });

                        // 表单提交前的验证
                        form.addEventListener('submit', function(e) {
                            if (!autoSearchCheckbox.checked && !bookIdInput.value.trim()) {
                                e.preventDefault();
                                alert("请启用自动搜索功能或输入图书ID");
                                return false;
                            }
                        });
                    });
                </script>
            </div>

        <?php elseif ($action === 'edit_tag' && isset($old_tag_name) && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <!-- 编辑标签页面 -->
            <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800">编辑标签</h2>
                    <a href="?action=tags" class="w-full sm:w-auto px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-arrow-left mr-2"></i> 返回列表
                    </a>
                </div>
                <form method="POST" action="?action=edit_tag&tag=<?php echo urlencode($old_tag_name); ?>" class="max-w-md">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">新标签名称 <span class="text-red-500">*</span></label>
                        <input type="text" name="new_tag_name" value="<?php echo htmlspecialchars($old_tag_name); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        <p class="text-xs text-gray-500 mt-1">如果新标签名已存在，系统会自动合并</p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <a href="?action=tags" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">取消</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">保存</button>
                    </div>
                </form>
            </div>

        <?php elseif ($action === 'merge_tags' && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <!-- 合并标签页面 -->
            <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800">合并标签</h2>
                    <a href="?action=tags" class="w-full sm:w-auto px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-arrow-left mr-2"></i> 返回列表
                    </a>
                </div>
                <form method="POST" action="?action=merge_tags" class="max-w-md">
                    <div class="space-y-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">源标签（将被合并的标签） <span class="text-red-500">*</span></label>
                            <input type="text" name="source_tag" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="输入要合并的标签名称" required>
                            <p class="text-xs text-gray-500 mt-1">该标签将被删除，其所有关联将转移到目标标签</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">目标标签（合并到的标签） <span class="text-red-500">*</span></label>
                            <input type="text" name="target_tag" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="输入目标标签名称" required>
                            <p class="text-xs text-gray-500 mt-1">源标签的所有关联将转移到此标签</p>
                        </div>
                    </div>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 p-4 rounded mb-4">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <span class="font-semibold">警告：</span>
                        </div>
                        <p class="text-sm mt-1">此操作不可逆，请确认源标签和目标标签名称正确！</p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <a href="?action=tags" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">取消</a>
                        <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">确认合并</button>
                    </div>
                </form>
            </div>

        <?php elseif ($action === 'view_tag_books' && isset($tag_books) && isset($tag_name)): ?>
            <!-- 查看标签关联书籍页面 -->
            <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <h2 class="text-2xl font-bold text-gray-800">标签 "<?php echo htmlspecialchars($tag_name); ?>" 关联的书籍</h2>
                    <a href="?action=tags" class="w-full sm:w-auto px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-arrow-left mr-2"></i> 返回列表
                    </a>
                </div>
                <div class="border rounded-lg overflow-hidden">
                    <?php if (empty($tag_books)): ?>
                        <div class="px-4 py-8 text-center text-gray-500">暂无关联书籍</div>
                    <?php else: ?>
                        <?php foreach ($tag_books as $book): ?>
                            <a href="?action=edit_book&id=<?php echo $book['id']; ?>" target="_blank" class="block py-2 px-4 hover:bg-gray-100 border-b transition-colors">
                                <?php echo htmlspecialchars($book['title']); ?> - <?php echo htmlspecialchars($book['author']); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($action === 'admin_settings'): ?>
            <!-- 管理员设置 -->
                <div class="bg-white rounded-lg shadow-sm p-4 lg:p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">管理员设置</h2>

                <form method="POST" class="max-w-md" action="?action=admin_settings">
                    <input type="hidden" name="admin_id" value="<?php echo $admin_user['id']; ?>">

                        <div class="space-y-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">用户名</label>
                                <input type="text" name="username" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($admin_user['username']); ?>" required>
                    </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">邮箱</label>
                                <input type="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="<?php echo htmlspecialchars($admin_user['email']); ?>" required>
                    </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">新密码（留空则不修改）</label>
                                <input type="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="留空则不修改密码">
                                <p class="text-xs text-gray-500 mt-1">如果不修改密码，请留空此字段</p>
                            </div>
                    </div>

                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                        <i class="fas fa-save mr-2"></i> 更新管理员信息
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    </main>

    <script>
        // 侧边栏切换
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        // 批量操作相关
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.user-checkbox, .book-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
            updateBatchActions();
        }

        function updateBatchActions() {
            const checkboxes = document.querySelectorAll('.user-checkbox:checked, .book-checkbox:checked');
            const batchActions = document.getElementById('batchActions');
            if (checkboxes.length > 0) {
                batchActions.classList.remove('hidden');
            } else {
                batchActions.classList.add('hidden');
            }
        }

        function clearSelection() {
            const checkboxes = document.querySelectorAll('.user-checkbox, .book-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            document.getElementById('selectAll').checked = false;
            updateBatchActions();
        }

        function batchDeleteUsers() {
            const checkboxes = document.querySelectorAll('.user-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('请选择要删除的用户');
                return;
            }
            if (!confirm(`确定要删除选中的 ${checkboxes.length} 个用户吗？此操作不可恢复！`)) {
                return;
            }
            const ids = Array.from(checkboxes).map(cb => cb.value).join(',');
            window.location.href = '?action=batch_delete_users&ids=' + ids + '&confirm=yes';
        }

        function batchDeleteBooks() {
            const checkboxes = document.querySelectorAll('.book-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('请选择要删除的电子书');
                return;
            }
            if (!confirm(`确定要删除选中的 ${checkboxes.length} 本电子书吗？此操作不可恢复！`)) {
                return;
            }
            const ids = Array.from(checkboxes).map(cb => cb.value).join(',');
            window.location.href = '?action=batch_delete_books&ids=' + ids + '&confirm=yes';
        }

        // 使用事件委托处理所有链接点击
        document.addEventListener('click', function(e) {
            const target = e.target.closest('a[data-action]');
            if (!target) return;
            
            e.preventDefault();
            e.stopPropagation();
            
            const action = target.getAttribute('data-action');
            
            // 处理侧边栏切换
            if (action === 'toggle-sidebar') {
                toggleSidebar();
                return;
            }
            
            // 处理批量删除用户
            if (action === 'batch-delete-users') {
                batchDeleteUsers();
                return;
            }
            
            // 处理批量删除书籍
            if (action === 'batch-delete-books') {
                batchDeleteBooks();
                return;
            }
            
            // 处理清除选择
            if (action === 'clear-selection') {
                clearSelection();
                return;
            }
        });
    </script>

</body>

</html>
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AdminMiddleware;
use App\Database\Connection;
use App\Service\UserService;
use App\Helper\TimeHelper;
use App\Helper\ViewHelper;
use PDO;

/**
 * 后台管理控制器
 * 
 * 处理后台管理页面的所有请求
 */
class AdminController extends BaseController
{
    private PDO $pdo;
    private const LIMIT = 20;

    public function __construct()
    {
        // 检查管理员权限
        AdminMiddleware::require();
        
        $this->pdo = Connection::getInstance();
    }

    /**
     * 处理请求
     */
    public function handle(array $params = []): void
    {
        $action = $params['action'] ?? $this->get('action', 'dashboard');

        // 处理登出
        if ($action === 'logout') {
            session_destroy();
            $this->redirect('index.php');
            return;
        }

        // 处理POST请求
        if ($this->isPost()) {
            $this->handlePost($action);
            return;
        }

        // 处理GET请求
        $this->handleGet($action);
    }

    /**
     * 处理POST请求
     */
    private function handlePost(string $action): void
    {
        switch ($action) {
            case 'add_user':
                $this->postAddUser();
                break;
            case 'edit_user':
                $this->postEditUser();
                break;
            case 'add_book':
                $this->postAddBook();
                break;
            case 'edit_book':
                $this->postEditBook();
                break;
            case 'add_tag':
                $this->postAddTag();
                break;
            case 'edit_tag':
                $this->postEditTag();
                break;
            case 'merge_tags':
                $this->postMergeTags();
                break;
            case 'admin_settings':
                $this->postAdminSettings();
                break;
            default:
                $this->handleGet($action);
        }
    }

    /**
     * 处理GET请求
     */
    private function handleGet(string $action): void
    {
        $message = $this->get('message');
        $error = $this->get('error');
        $page = $this->getPage();
        $limit = self::LIMIT;
        $offset = ($page - 1) * $limit;

        switch ($action) {
            case 'dashboard':
                $this->getDashboard();
                break;
            case 'users':
                $this->getUsers($page, $limit, $offset, $message, $error);
                break;
            case 'add_user':
                $this->getAddUser($error);
                break;
            case 'edit_user':
                $this->getEditUser($error);
                break;
            case 'delete_user':
                $this->getDeleteUser();
                break;
            case 'batch_delete_users':
                $this->getBatchDeleteUsers();
                break;
            case 'books':
                $this->getBooks($page, $limit, $offset, $message, $error);
                break;
            case 'add_book':
                $this->getAddBook($error);
                break;
            case 'edit_book':
                $this->getEditBook($error);
                break;
            case 'delete_book':
                $this->getDeleteBook();
                break;
            case 'batch_delete_books':
                $this->getBatchDeleteBooks();
                break;
            case 'downloads':
                $this->getDownloads($page, $limit, $offset);
                break;
            case 'search_history':
                $this->getSearchHistory($page, $limit, $offset);
                break;
            case 'tags':
                $this->getTags($page, $limit, $offset, $message, $error);
                break;
            case 'add_tag':
                $this->getAddTag($error);
                break;
            case 'edit_tag':
                $this->getEditTag($error);
                break;
            case 'delete_tag':
                $this->getDeleteTag();
                break;
            case 'merge_tags':
                $this->getMergeTags($error);
                break;
            case 'view_tag_books':
                $this->getViewTagBooks();
                break;
            case 'admin_settings':
                $this->getAdminSettings($message, $error);
                break;
            default:
                $this->getDashboard();
        }
    }

    // ==================== Dashboard ====================

    private function getDashboard(): void
    {
        $stats = [
            'total_users' => $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'total_books' => $this->pdo->query("SELECT COUNT(*) FROM books")->fetchColumn(),
            'total_downloads' => $this->pdo->query("SELECT COUNT(*) FROM download_logs")->fetchColumn(),
            'today_downloads' => $this->pdo->query("SELECT COUNT(*) FROM download_logs WHERE DATE(download_time) = CURDATE()")->fetchColumn()
        ];

        // 获取近7天用户增长数据
        $stmt = $this->pdo->prepare("
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

        foreach ($user_growth_data as $data) {
            $daily_user_counts[$data['date']] = (int)$data['user_count'];
        }

        // 获取近7天电子书增长数据
        $stmt = $this->pdo->prepare("
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

        $daily_book_counts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $daily_book_counts[$date] = 0;
        }

        foreach ($book_growth_data as $data) {
            $daily_book_counts[$data['date']] = (int)$data['book_count'];
        }

        $this->renderWithLayout('admin/dashboard', [
            'action' => 'dashboard',
            'pageTitle' => '仪表盘 - 七秒电子书后台管理',
            'stats' => $stats,
            'dates' => $dates,
            'daily_user_counts' => $daily_user_counts,
            'daily_book_counts' => $daily_book_counts,
        ]);
    }

    // ==================== Users ====================

    private function getUsers(int $page, int $limit, int $offset, ?string $message, ?string $error): void
    {
        $user_search = trim($this->get('search', ''));
        
        $where_clause = '';
        $params = [];
        if (!empty($user_search)) {
            $where_clause = 'WHERE username LIKE :search OR email LIKE :search';
            $params[':search'] = '%' . $user_search . '%';
        }
        
        $sql = "SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $count_sql = "SELECT COUNT(*) FROM users $where_clause";
        $count_stmt = $this->pdo->prepare($count_sql);
        foreach ($params as $key => $value) {
            $count_stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $count_stmt->execute();
        $total_users = $count_stmt->fetchColumn();
        $total_pages = ceil($total_users / $limit);

        $this->renderWithLayout('admin/users', [
            'action' => 'users',
            'pageTitle' => '用户管理 - 七秒电子书后台管理',
            'users' => $users,
            'user_search' => $user_search,
            'page' => $page,
            'total_pages' => $total_pages,
            'total_users' => $total_users,
            'limit' => $limit,
            'message' => $message,
            'error' => $error,
        ]);
    }

    private function getAddUser(?string $error): void
    {
        $this->renderWithLayout('admin/add_user', [
            'action' => 'add_user',
            'pageTitle' => '添加用户 - 七秒电子书后台管理',
            'error' => $error,
        ]);
    }

    private function postAddUser(): void
    {
        $username = trim($this->post('username', ''));
        $password = $this->post('password', '');
        $email = trim($this->post('email', ''));
        $role = $this->post('role', 'user');

        if ($username && $password && $email) {
            $userService = new UserService();
            $username_validation = $userService->validateUsername($username);
            if (!$username_validation['valid']) {
                $this->redirect('?action=add_user&error=' . urlencode($username_validation['message']));
                return;
            }

            $password_validation = $userService->validatePassword($password);
            if (!$password_validation['valid']) {
                $this->redirect('?action=add_user&error=' . urlencode($password_validation['message']));
                return;
            }

            $email = strtolower($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->redirect('?action=add_user&error=' . urlencode('请输入有效的邮箱地址'));
                return;
            }

            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $this->redirect('?action=add_user&error=' . urlencode('用户名已存在'));
                return;
            }

            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $this->redirect('?action=add_user&error=' . urlencode('该邮箱已被注册'));
                return;
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO users (username, password_hash, email, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $password_hash, $email, $role]);
            $this->redirect('?action=users&message=' . urlencode('用户添加成功'));
        } else {
            $this->redirect('?action=add_user&error=' . urlencode('请填写完整信息'));
        }
    }

    private function getEditUser(?string $error): void
    {
        $user_id = intval($this->get('id', 0));
        if (!$user_id) {
            $this->redirect('?action=users&error=' . urlencode('未指定用户ID'));
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$edit_user) {
            $this->redirect('?action=users&error=' . urlencode('用户不存在'));
            return;
        }

        $this->renderWithLayout('admin/edit_user', [
            'action' => 'edit_user',
            'pageTitle' => '编辑用户 - 七秒电子书后台管理',
            'edit_user' => $edit_user,
            'error' => $error,
        ]);
    }

    private function postEditUser(): void
    {
        $user_id = intval($this->get('id', 0));
        if (!$user_id) {
            $this->redirect('?action=users&error=' . urlencode('未指定用户ID'));
            return;
        }

        $username = trim($this->post('username', ''));
        $email = trim($this->post('email', ''));
        $role = $this->post('role', 'user');
        $new_password = $this->post('password', '');

        if ($username && $email) {
            $userService = new UserService();
            $username_validation = $userService->validateUsername($username);
            if (!$username_validation['valid']) {
                $this->redirect('?action=edit_user&id=' . $user_id . '&error=' . urlencode($username_validation['message']));
                return;
            }

            $email = strtolower($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->redirect('?action=edit_user&id=' . $user_id . '&error=' . urlencode('请输入有效的邮箱地址'));
                return;
            }

            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user_id]);
            if ($stmt->fetch()) {
                $this->redirect('?action=edit_user&id=' . $user_id . '&error=' . urlencode('用户名已被其他用户使用'));
                return;
            }

            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $this->redirect('?action=edit_user&id=' . $user_id . '&error=' . urlencode('该邮箱已被其他用户使用'));
                return;
            }

            if ($new_password) {
                $password_validation = $userService->validatePassword($new_password);
                if (!$password_validation['valid']) {
                    $this->redirect('?action=edit_user&id=' . $user_id . '&error=' . urlencode($password_validation['message']));
                    return;
                }
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, password_hash = ? WHERE id = ?");
                $stmt->execute([$username, $email, $role, $password_hash, $user_id]);
            } else {
                $stmt = $this->pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $email, $role, $user_id]);
            }

            $this->redirect('?action=users&message=' . urlencode('用户信息更新成功'));
        } else {
            $this->redirect('?action=edit_user&id=' . $user_id . '&error=' . urlencode('请填写完整信息'));
        }
    }

    private function getDeleteUser(): void
    {
        $user_id = intval($this->get('id', 0));
        $confirm = $this->get('confirm', '');
        
        if ($user_id && $confirm === 'yes') {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $this->redirect('?action=users&message=' . urlencode('用户删除成功'));
        } elseif ($user_id) {
            $stmt = $this->pdo->prepare("SELECT id, username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $delete_user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($delete_user) {
                $this->renderWithLayout('admin/delete_user', [
                    'action' => 'delete_user',
                    'pageTitle' => '删除用户 - 七秒电子书后台管理',
                    'delete_user' => $delete_user,
                ]);
            } else {
                $this->redirect('?action=users&error=' . urlencode('用户不存在'));
            }
        } else {
            $this->redirect('?action=users&error=' . urlencode('未指定用户ID'));
        }
    }

    private function getBatchDeleteUsers(): void
    {
        $user_ids = $this->get('ids', '');
        $confirm = $this->get('confirm', '');
        
        if ($user_ids && $confirm === 'yes') {
            $ids = explode(',', $user_ids);
            $ids = array_filter(array_map('intval', $ids));
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $this->pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                $this->redirect('?action=users&message=' . urlencode('已成功删除 ' . count($ids) . ' 个用户'));
            }
        }
        $this->redirect('?action=users');
    }

    // ==================== Books ====================

    private function getBooks(int $page, int $limit, int $offset, ?string $message, ?string $error): void
    {
        $book_search = trim($this->get('search', ''));
        
        $where_clause = '';
        $params = [];
        if (!empty($book_search)) {
            $where_clause = 'WHERE b.title LIKE :search OR b.author LIKE :search';
            $params[':search'] = '%' . $book_search . '%';
        }
        
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
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $count_sql = "SELECT COUNT(DISTINCT b.id) FROM books b $where_clause";
        $count_stmt = $this->pdo->prepare($count_sql);
        foreach ($params as $key => $value) {
            $count_stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $count_stmt->execute();
        $total_books = $count_stmt->fetchColumn();
        $total_pages = ceil($total_books / $limit);

        $this->renderWithLayout('admin/books', [
            'action' => 'books',
            'pageTitle' => '书籍管理 - 七秒电子书后台管理',
            'books' => $books,
            'book_search' => $book_search,
            'page' => $page,
            'total_pages' => $total_pages,
            'total_books' => $total_books,
            'limit' => $limit,
            'message' => $message,
            'error' => $error,
        ]);
    }

    private function getAddBook(?string $error): void
    {
        $this->renderWithLayout('admin/add_book', [
            'action' => 'add_book',
            'pageTitle' => '添加电子书 - 七秒电子书后台管理',
            'error' => $error,
        ]);
    }

    private function postAddBook(): void
    {
        $title = trim($this->post('title', ''));
        $author = trim($this->post('author', ''));
        $description = trim($this->post('description', ''));
        $category = trim($this->post('category', ''));
        $publisher = trim($this->post('publisher', ''));
        $page_count = intval($this->post('page_count', 0));
        $file_name = trim($this->post('file_name', ''));
        $file_path = trim($this->post('file_path', ''));
        $file_size = trim($this->post('file_size', ''));
        $file_format = trim($this->post('file_format', ''));
        $tags = trim($this->post('tags', ''));

        if ($title && $author) {
            $current_time = TimeHelper::getCurrentTime();
            $stmt = $this->pdo->prepare("
                INSERT INTO books (title, author, description, category, publisher, page_count, file_name, file_path, file_size, file_format, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $author, $description, $category, $publisher, $page_count, $file_name, $file_path, $file_size, $file_format, $current_time]);

            $book_id = $this->pdo->lastInsertId();

            if ($tags) {
                $tag_list = array_filter(array_map('trim', explode(',', $tags)));
                $current_time = TimeHelper::getCurrentTime();
                foreach ($tag_list as $tag) {
                    if ($tag) {
                        $tag_stmt = $this->pdo->prepare("INSERT INTO book_tags (book_id, tag_name, created_at) VALUES (?, ?, ?)");
                        $tag_stmt->execute([$book_id, $tag, $current_time]);
                    }
                }
            }

            $this->redirect('?action=books&message=' . urlencode('电子书添加成功'));
        } else {
            $this->redirect('?action=add_book&error=' . urlencode('请填写必填字段（标题、作者）'));
        }
    }

    private function getEditBook(?string $error): void
    {
        $book_id = intval($this->get('id', 0));
        if (!$book_id) {
            $this->redirect('?action=books&error=' . urlencode('未指定电子书ID'));
            return;
        }

        $stmt = $this->pdo->prepare("
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
            $this->redirect('?action=books&error=' . urlencode('电子书不存在'));
            return;
        }

        $this->renderWithLayout('admin/edit_book', [
            'action' => 'edit_book',
            'pageTitle' => '编辑电子书 - 七秒电子书后台管理',
            'book' => $book,
            'error' => $error,
        ]);
    }

    private function postEditBook(): void
    {
        $book_id = intval($this->get('id', 0));
        if (!$book_id) {
            $this->redirect('?action=books&error=' . urlencode('未指定电子书ID'));
            return;
        }

        $title = trim($this->post('title', ''));
        $author = trim($this->post('author', ''));
        $description = trim($this->post('description', ''));
        $category = trim($this->post('category', ''));
        $publisher = trim($this->post('publisher', ''));
        $page_count = intval($this->post('page_count', 0));
        $file_name = trim($this->post('file_name', ''));
        $file_path = trim($this->post('file_path', ''));
        $file_size = trim($this->post('file_size', ''));
        $file_format = trim($this->post('file_format', ''));
        $tags = trim($this->post('tags', ''));

        if ($title && $author) {
            $current_time = TimeHelper::getCurrentTime();
            $stmt = $this->pdo->prepare("
                UPDATE books 
                SET title = ?, author = ?, description = ?, category = ?, publisher = ?, page_count = ?, 
                    file_name = ?, file_path = ?, file_size = ?, file_format = ?, updated_at = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $author, $description, $category, $publisher, $page_count, $file_name, $file_path, $file_size, $file_format, $current_time, $book_id]);

            $stmt = $this->pdo->prepare("DELETE FROM book_tags WHERE book_id = ?");
            $stmt->execute([$book_id]);

            if ($tags) {
                $tag_list = array_filter(array_map('trim', explode(',', $tags)));
                $current_time = TimeHelper::getCurrentTime();
                foreach ($tag_list as $tag) {
                    if ($tag) {
                        $tag_stmt = $this->pdo->prepare("INSERT INTO book_tags (book_id, tag_name, created_at) VALUES (?, ?, ?)");
                        $tag_stmt->execute([$book_id, $tag, $current_time]);
                    }
                }
            }

            $this->redirect('?action=books&message=' . urlencode('电子书信息更新成功'));
        } else {
            $this->redirect('?action=edit_book&id=' . $book_id . '&error=' . urlencode('请填写必填字段（标题、作者）'));
        }
    }

    private function getDeleteBook(): void
    {
        $book_id = intval($this->get('id', 0));
        $confirm = $this->get('confirm', '');
        
        if ($book_id && $confirm === 'yes') {
            $stmt = $this->pdo->prepare("DELETE FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $this->redirect('?action=books&message=' . urlencode('电子书删除成功'));
        } elseif ($book_id) {
            $stmt = $this->pdo->prepare("SELECT id, title FROM books WHERE id = ?");
            $stmt->execute([$book_id]);
            $delete_book = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($delete_book) {
                $this->renderWithLayout('admin/delete_book', [
                    'action' => 'delete_book',
                    'pageTitle' => '删除电子书 - 七秒电子书后台管理',
                    'delete_book' => $delete_book,
                ]);
            } else {
                $this->redirect('?action=books&error=' . urlencode('电子书不存在'));
            }
        } else {
            $this->redirect('?action=books&error=' . urlencode('未指定电子书ID'));
        }
    }

    private function getBatchDeleteBooks(): void
    {
        $book_ids = $this->get('ids', '');
        $confirm = $this->get('confirm', '');
        
        if ($book_ids && $confirm === 'yes') {
            $ids = explode(',', $book_ids);
            $ids = array_filter(array_map('intval', $ids));
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $this->pdo->prepare("DELETE FROM books WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                $this->redirect('?action=books&message=' . urlencode('已成功删除 ' . count($ids) . ' 本电子书'));
            }
        }
        $this->redirect('?action=books');
    }

    // ==================== Downloads ====================

    private function getDownloads(int $page, int $limit, int $offset): void
    {
        $stmt = $this->pdo->prepare("
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

        $total_downloads = $this->pdo->query("SELECT COUNT(*) FROM download_logs")->fetchColumn();
        $total_pages = ceil($total_downloads / $limit);

        $stmt = $this->pdo->prepare("
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

        $daily_download_counts = [];
        $download_dates = [];
        for ($i = 27; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $download_dates[] = $date;
            $daily_download_counts[$date] = 0;
        }

        foreach ($download_trend_data as $data) {
            $daily_download_counts[$data['date']] = (int)$data['download_count'];
        }

        // 优化查询：先聚合download_logs，再JOIN books，避免全表扫描
        $stmt = $this->pdo->prepare("
            SELECT 
                b.id,
                b.title,
                b.author,
                dl_stats.download_count
            FROM (
                SELECT 
                    book_id,
                    COUNT(*) as download_count
                FROM download_logs
                GROUP BY book_id
                ORDER BY download_count DESC
                LIMIT 10
            ) dl_stats
            INNER JOIN books b ON b.id = dl_stats.book_id
            ORDER BY dl_stats.download_count DESC
        ");
        $stmt->execute();
        $top_books = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->renderWithLayout('admin/downloads', [
            'action' => 'downloads',
            'pageTitle' => '下载记录 - 七秒电子书后台管理',
            'downloads' => $downloads,
            'page' => $page,
            'total_pages' => $total_pages,
            'total_downloads' => $total_downloads,
            'limit' => $limit,
            'download_dates' => $download_dates,
            'daily_download_counts' => $daily_download_counts,
            'top_books' => $top_books,
        ]);
    }

    // ==================== Search History ====================

    private function getSearchHistory(int $page, int $limit, int $offset): void
    {
        $search_keyword = trim($this->get('search', ''));
        
        $where_clause = '';
        $params = [];
        if (!empty($search_keyword)) {
            $where_clause = 'WHERE sh.search_query LIKE :search';
            $params[':search'] = '%' . $search_keyword . '%';
        }
        
        $sql = "
            SELECT sh.*, u.username
            FROM search_history sh
            LEFT JOIN users u ON sh.user_id = u.id
            $where_clause
            ORDER BY sh.search_time DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $search_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $count_sql = "SELECT COUNT(*) FROM search_history sh $where_clause";
        $count_stmt = $this->pdo->prepare($count_sql);
        foreach ($params as $key => $value) {
            $count_stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $count_stmt->execute();
        $total_search_records = $count_stmt->fetchColumn();
        $total_pages = ceil($total_search_records / $limit);
        
        $stmt = $this->pdo->prepare("
            SELECT 
                search_query,
                COUNT(*) as search_count,
                COUNT(DISTINCT user_id) as user_count,
                COUNT(DISTINCT ip_address) as ip_count
            FROM search_history
            WHERE search_query != ''
            GROUP BY search_query
            ORDER BY search_count DESC
            LIMIT 20
        ");
        $stmt->execute();
        $popular_searches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->renderWithLayout('admin/search_history', [
            'action' => 'search_history',
            'pageTitle' => '搜索记录 - 七秒电子书后台管理',
            'search_records' => $search_records,
            'search_keyword' => $search_keyword,
            'page' => $page,
            'total_pages' => $total_pages,
            'total_search_records' => $total_search_records,
            'limit' => $limit,
            'popular_searches' => $popular_searches,
        ]);
    }

    // ==================== Tags ====================

    private function getTags(int $page, int $limit, int $offset, ?string $message, ?string $error): void
    {
        $tag_search = trim($this->get('search', ''));

        $where_clause = '';
        $params = [];
        if (!empty($tag_search)) {
            $where_clause = 'WHERE bt.tag_name LIKE :search';
            $params[':search'] = '%' . $tag_search . '%';
        }

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

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $count_sql = "SELECT COUNT(DISTINCT tag_name) FROM book_tags $where_clause";
        $count_stmt = $this->pdo->prepare($count_sql);
        foreach ($params as $key => $value) {
            $count_stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $count_stmt->execute();
        $total_tags = $count_stmt->fetchColumn();
        $total_pages = ceil($total_tags / $limit);

        $this->renderWithLayout('admin/tags', [
            'action' => 'tags',
            'pageTitle' => '标签管理 - 七秒电子书后台管理',
            'tags' => $tags,
            'tag_search' => $tag_search,
            'page' => $page,
            'total_pages' => $total_pages,
            'total_tags' => $total_tags,
            'limit' => $limit,
            'message' => $message,
            'error' => $error,
        ]);
    }

    private function getAddTag(?string $error): void
    {
        $this->renderWithLayout('admin/add_tag', [
            'action' => 'add_tag',
            'pageTitle' => '添加标签 - 七秒电子书后台管理',
            'error' => $error,
        ]);
    }

    private function postAddTag(): void
    {
        $tag_name = trim($this->post('tag_name', ''));
        $auto_search = isset($_POST['auto_search']) && $_POST['auto_search'] == '1';
        $book_id = intval($this->post('book_id', 0));

        if ($tag_name) {
            $current_time = TimeHelper::getCurrentTime();
            $added_count = 0;

            if ($auto_search) {
                $search_term = '%' . $tag_name . '%';
                $stmt = $this->pdo->prepare("
                    SELECT DISTINCT id 
                    FROM books 
                    WHERE (title LIKE ? OR author LIKE ?)
                ");
                $stmt->execute([$search_term, $search_term]);
                $matching_books = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($matching_books)) {
                    foreach ($matching_books as $bid) {
                        $check_stmt = $this->pdo->prepare("SELECT COUNT(*) FROM book_tags WHERE book_id = ? AND tag_name = ?");
                        $check_stmt->execute([$bid, $tag_name]);
                        $exists = $check_stmt->fetchColumn();

                        if (!$exists) {
                            $insert_stmt = $this->pdo->prepare("INSERT INTO book_tags (book_id, tag_name, created_at) VALUES (?, ?, ?)");
                            $insert_stmt->execute([$bid, $tag_name, $current_time]);
                            $added_count++;
                        }
                    }
                    
                    if ($added_count > 0) {
                        $this->redirect('?action=tags&message=' . urlencode("标签添加成功！已为 {$added_count} 本匹配的书籍添加了标签"));
                    } else {
                        $this->redirect('?action=tags&message=' . urlencode('未找到新的匹配书籍，所有匹配的书籍已包含此标签'));
                    }
                } else {
                    $this->redirect('?action=tags&message=' . urlencode('未找到匹配的书籍'));
                }
            } else {
                if ($book_id) {
                    $check_stmt = $this->pdo->prepare("SELECT COUNT(*) FROM book_tags WHERE book_id = ? AND tag_name = ?");
                    $check_stmt->execute([$book_id, $tag_name]);
                    $exists = $check_stmt->fetchColumn();

                    if (!$exists) {
                        $stmt = $this->pdo->prepare("INSERT INTO book_tags (book_id, tag_name, created_at) VALUES (?, ?, ?)");
                        $stmt->execute([$book_id, $tag_name, $current_time]);
                        $this->redirect('?action=tags&message=' . urlencode('标签添加成功'));
                    } else {
                        $this->redirect('?action=tags&message=' . urlencode('该书籍已包含此标签'));
                    }
                } else {
                    $this->redirect('?action=add_tag&error=' . urlencode('请启用自动搜索功能或输入图书ID'));
                }
            }
        } else {
            $this->redirect('?action=add_tag&error=' . urlencode('请输入标签名称'));
        }
    }

    private function getEditTag(?string $error): void
    {
        $old_tag_name = trim($this->get('tag', ''));
        if (!$old_tag_name) {
            $this->redirect('?action=tags&error=' . urlencode('未指定标签名称'));
            return;
        }

        $this->renderWithLayout('admin/edit_tag', [
            'action' => 'edit_tag',
            'pageTitle' => '编辑标签 - 七秒电子书后台管理',
            'old_tag_name' => $old_tag_name,
            'error' => $error,
        ]);
    }

    private function postEditTag(): void
    {
        $old_tag_name = trim($this->get('tag', ''));
        if (!$old_tag_name) {
            $this->redirect('?action=tags&error=' . urlencode('未指定标签名称'));
            return;
        }

        $new_tag_name = trim($this->post('new_tag_name', ''));

        if ($new_tag_name && $old_tag_name !== $new_tag_name) {
            $check_stmt = $this->pdo->prepare("SELECT COUNT(*) FROM book_tags WHERE tag_name = ?");
            $check_stmt->execute([$new_tag_name]);
            $exists = $check_stmt->fetchColumn();

            if ($exists > 0) {
                $update_stmt = $this->pdo->prepare("UPDATE book_tags SET tag_name = ? WHERE tag_name = ?");
                $update_stmt->execute([$new_tag_name, $old_tag_name]);

                $delete_dup_stmt = $this->pdo->prepare("
                    DELETE t1 FROM book_tags t1
                    INNER JOIN book_tags t2 
                    WHERE t1.id > t2.id 
                    AND t1.tag_name = t2.tag_name 
                    AND t1.book_id = t2.book_id
                ");
                $delete_dup_stmt->execute();

                $this->redirect('?action=tags&message=' . urlencode('标签已重命名并合并'));
            } else {
                $update_stmt = $this->pdo->prepare("UPDATE book_tags SET tag_name = ? WHERE tag_name = ?");
                $update_stmt->execute([$new_tag_name, $old_tag_name]);
                $this->redirect('?action=tags&message=' . urlencode('标签重命名成功'));
            }
        } else {
            $this->redirect('?action=edit_tag&tag=' . urlencode($old_tag_name) . '&error=' . urlencode('标签名称不能为空或相同'));
        }
    }

    private function getDeleteTag(): void
    {
        $tag_name = trim($this->get('tag', ''));
        $confirm = $this->get('confirm', '');
        
        if ($tag_name && $confirm === 'yes') {
            $stmt = $this->pdo->prepare("DELETE FROM book_tags WHERE tag_name = ?");
            $stmt->execute([$tag_name]);
            $this->redirect('?action=tags&message=' . urlencode('标签删除成功'));
        } else {
            $this->redirect('?action=tags');
        }
    }

    private function getMergeTags(?string $error): void
    {
        $this->renderWithLayout('admin/merge_tags', [
            'action' => 'merge_tags',
            'pageTitle' => '合并标签 - 七秒电子书后台管理',
            'error' => $error,
        ]);
    }

    private function postMergeTags(): void
    {
        $source_tag = trim($this->post('source_tag', ''));
        $target_tag = trim($this->post('target_tag', ''));

        if ($source_tag && $target_tag && $source_tag !== $target_tag) {
            $check_stmt = $this->pdo->prepare("SELECT COUNT(*) FROM book_tags WHERE tag_name = ?");
            $check_stmt->execute([$target_tag]);
            $target_exists = $check_stmt->fetchColumn();

            if ($target_exists == 0) {
                $update_stmt = $this->pdo->prepare("UPDATE book_tags SET tag_name = ? WHERE tag_name = ?");
                $update_stmt->execute([$target_tag, $source_tag]);
                $this->redirect('?action=tags&message=' . urlencode('标签合并成功（源标签已重命名为目标标签）'));
            } else {
                $current_time = TimeHelper::getCurrentTime();
                $merge_stmt = $this->pdo->prepare("
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

                $delete_stmt = $this->pdo->prepare("DELETE FROM book_tags WHERE tag_name = ?");
                $delete_stmt->execute([$source_tag]);

                $this->redirect('?action=tags&message=' . urlencode('标签合并成功'));
            }
        } else {
            $this->redirect('?action=merge_tags&error=' . urlencode('请选择要合并的标签，且源标签和目标标签不能相同'));
        }
    }

    private function getViewTagBooks(): void
    {
        $tag_name = trim($this->get('tag', ''));
        if ($tag_name) {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT b.id, b.title, b.author
                FROM books b
                INNER JOIN book_tags bt ON b.id = bt.book_id
                WHERE bt.tag_name = ?
                ORDER BY b.id
            ");
            $stmt->execute([$tag_name]);
            $tag_books = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->renderWithLayout('admin/view_tag_books', [
                'action' => 'view_tag_books',
                'pageTitle' => '标签书籍列表 - 七秒电子书后台管理',
                'tag_name' => $tag_name,
                'tag_books' => $tag_books,
            ]);
        } else {
            $this->redirect('?action=tags&error=' . urlencode('未指定标签名称'));
        }
    }

    // ==================== Admin Settings ====================

    private function getAdminSettings(?string $message, ?string $error): void
    {
        $stmt = $this->pdo->prepare("SELECT id, username, email FROM users WHERE role = 'admin' LIMIT 1");
        $stmt->execute();
        $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->renderWithLayout('admin/admin_settings', [
            'action' => 'admin_settings',
            'pageTitle' => '管理员设置 - 七秒电子书后台管理',
            'admin_user' => $admin_user,
            'message' => $message,
            'error' => $error,
        ]);
    }

    private function postAdminSettings(): void
    {
        $admin_id = intval($this->post('admin_id', 0));
        $username = trim($this->post('username', ''));
        $email = trim($this->post('email', ''));
        $password = $this->post('password', '');

        if ($admin_id && $username && $email) {
            $userService = new UserService();
            $username_validation = $userService->validateUsername($username);
            if (!$username_validation['valid']) {
                $this->redirect('?action=admin_settings&error=' . urlencode($username_validation['message']));
                return;
            }

            $email = strtolower($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->redirect('?action=admin_settings&error=' . urlencode('请输入有效的邮箱地址'));
                return;
            }

            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $admin_id]);
            if ($stmt->fetch()) {
                $this->redirect('?action=admin_settings&error=' . urlencode('用户名已被其他用户使用'));
                return;
            }

            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ? AND id != ?");
            $stmt->execute([$email, $admin_id]);
            if ($stmt->fetch()) {
                $this->redirect('?action=admin_settings&error=' . urlencode('该邮箱已被其他用户使用'));
                return;
            }

            if ($password) {
                $password_validation = $userService->validatePassword($password);
                if (!$password_validation['valid']) {
                    $this->redirect('?action=admin_settings&error=' . urlencode($password_validation['message']));
                    return;
                }
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->pdo->prepare("UPDATE users SET username = ?, email = ?, password_hash = ? WHERE id = ?");
                $stmt->execute([$username, $email, $password_hash, $admin_id]);
            } else {
                $stmt = $this->pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $stmt->execute([$username, $email, $admin_id]);
            }

            $_SESSION['admin_username'] = $username;
            $this->redirect('?action=admin_settings&message=' . urlencode('管理员信息更新成功'));
        } else {
            $this->redirect('?action=admin_settings&error=' . urlencode('请填写用户名和邮箱'));
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Service\BookService;
use App\Service\BookLikeService;
use App\Service\DownloadService;
use App\Service\UserService;
use App\Service\SearchService;
use App\Service\SearchBuilderService;
use App\Helper\TimeHelper;
use App\Helper\NetworkHelper;
use App\Helper\StringHelper;
use App\Database\Connection;
use App\Middleware\AuthMiddleware;
use App\Config\Config;
use PDO;

/**
 * 前台控制器
 * 
 * 处理前台页面的所有请求
 */
class FrontController extends BaseController
{
    private PDO $pdo;
    private Auth $auth;
    private BookService $bookService;
    private BookLikeService $bookLikeService;
    private DownloadService $downloadService;
    private UserService $userService;
    private SearchService $searchService;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
        $this->auth = new Auth();
        $this->bookService = new BookService();
        $this->bookLikeService = new BookLikeService();
        $this->downloadService = new DownloadService();
        $this->userService = new UserService();
        $this->searchService = new SearchService();
    }

    /**
     * 处理请求
     */
    public function handle(array $params = []): void
    {
        $action = $params['action'] ?? $this->get('action', 'index');

        // 处理POST请求（登录、注册等）
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
            case 'login':
                $this->handleLogin();
                break;
            case 'register':
                $this->handleRegister();
                break;
            case 'forgot_password':
                $this->handleForgotPassword();
                break;
            case 'reset_password':
                $this->handleResetPassword();
                break;
            case 'update_profile':
                $this->handleUpdateProfile();
                break;
            case 'like':
                $this->handleLike();
                break;
            case 'unlike':
                $this->handleUnlike();
                break;
            case 'favorite':
                $this->handleFavorite();
                break;
            case 'unfavorite':
                $this->handleUnfavorite();
                break;
            default:
                $this->redirect('index.php');
        }
    }

    /**
     * 处理GET请求
     */
    private function handleGet(string $action): void
    {
        // 处理登出
        if ($this->get('logout')) {
            $this->handleLogout();
            return;
        }

        switch ($action) {
            case 'index':
            case 'search':
                $this->index();
                break;
            case 'detail':
                $this->detail();
                break;
            case 'download':
                $this->download();
                break;
            case 'login':
                $this->showLogin();
                break;
            case 'register':
                $this->showRegister();
                break;
            case 'forgot_password':
                $this->showForgotPassword();
                break;
            case 'reset_password':
                $this->showResetPassword();
                break;
            case 'profile':
                $this->profile();
                break;
            case 'my_likes':
                $this->myLikes();
                break;
            case 'my_favorites':
                $this->myFavorites();
                break;
            case 'popular_searches':
                $this->popularSearches();
                break;
            case 'download_ranking':
                $this->downloadRanking();
                break;
            case 'latest':
                $this->latest();
                break;
            case 'generate_sitemap':
                $this->generateSitemap(true);
                break;
            default:
                $this->index();
        }
    }

    // ========== POST处理方法 ==========

    /**
     * 处理登录
     */
    private function handleLogin(): void
    {
        $username = trim($this->post('username', ''));
        $password = $this->post('password', '');

        if (!$username || !$password) {
            $this->showLogin(['error' => '请输入用户名和密码']);
            return;
        }

        $user = $this->auth->verifyLogin($username, $password);
        if ($user) {
            $this->auth->setUserSession($user);
            $this->redirect('index.php');
        } else {
            $this->showLogin(['error' => '用户名或密码错误']);
        }
    }

    /**
     * 处理注册
     */
    private function handleRegister(): void
    {
        $username = trim($this->post('reg_username', ''));
        $password = $this->post('reg_password', '');
        $email = trim($this->post('reg_email', ''));

        if (!$username || !$password || !$email) {
            $this->showRegister(['error' => '请填写完整信息']);
            return;
        }

        // 验证用户名
        $usernameValidation = $this->userService->validateUsername($username);
        if (!$usernameValidation['valid']) {
            $this->showRegister(['error' => $usernameValidation['message']]);
            return;
        }

        // 验证密码
        $passwordValidation = $this->userService->validatePassword($password);
        if (!$passwordValidation['valid']) {
            $this->showRegister(['error' => $passwordValidation['message']]);
            return;
        }

        // 验证邮箱
        $email = strtolower($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->showRegister(['error' => '请输入有效的邮箱地址']);
            return;
        }

        // 检查用户名是否已存在
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $this->showRegister(['error' => '用户名已存在']);
            return;
        }

        // 检查邮箱是否已存在
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $this->showRegister(['error' => '该邮箱已被注册']);
            return;
        }

        // 插入新用户
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $currentTime = TimeHelper::getCurrentTime();
        $stmt = $this->pdo->prepare("INSERT INTO users (username, password_hash, email, created_at) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$username, $passwordHash, $email, $currentTime])) {
            // 注册成功后自动登录
            $stmt = $this->pdo->prepare("SELECT id, username, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($user) {
                $this->auth->setUserSession($user);
                $this->redirect('index.php');
            } else {
                $this->showLogin(['success' => '注册成功，请登录']);
            }
        } else {
            $this->showRegister(['error' => '注册失败']);
        }
    }

    /**
     * 处理登出
     */
    private function handleLogout(): void
    {
        $this->auth->clearUserSession();
        session_destroy();
        
        $query = $_GET;
        unset($query['logout']);
        $redirectUrl = 'index.php';
        if (!empty($query)) {
            $redirectUrl .= '?' . http_build_query($query);
        }
        
        $this->redirect($redirectUrl);
    }

    /**
     * 处理点赞
     */
    private function handleLike(): void
    {
        if (!AuthMiddleware::check()) {
            $this->json(['success' => false, 'message' => '请先登录'], 401);
            return;
        }

        $userId = $this->auth->getUserId();
        $bookId = (int)$this->post('book_id', 0);

        if ($bookId <= 0) {
            $this->json(['success' => false, 'message' => '无效的书籍ID'], 400);
            return;
        }

        $result = $this->bookLikeService->addLike($userId, $bookId);
        $result['like_count'] = $this->bookLikeService->getBookLikeCount($bookId);
        
        $this->json($result);
    }

    /**
     * 处理取消点赞
     */
    private function handleUnlike(): void
    {
        if (!AuthMiddleware::check()) {
            $this->json(['success' => false, 'message' => '请先登录'], 401);
            return;
        }

        $userId = $this->auth->getUserId();
        $bookId = (int)$this->post('book_id', 0);

        if ($bookId <= 0) {
            $this->json(['success' => false, 'message' => '无效的书籍ID'], 400);
            return;
        }

        $result = $this->bookLikeService->removeLike($userId, $bookId);
        $result['like_count'] = $this->bookLikeService->getBookLikeCount($bookId);
        
        $this->json($result);
    }

    /**
     * 处理收藏
     */
    private function handleFavorite(): void
    {
        if (!AuthMiddleware::check()) {
            $this->json(['success' => false, 'message' => '请先登录'], 401);
            return;
        }

        $userId = $this->auth->getUserId();
        $bookId = (int)$this->post('book_id', 0);

        if ($bookId <= 0) {
            $this->json(['success' => false, 'message' => '无效的书籍ID'], 400);
            return;
        }

        $result = $this->bookLikeService->addFavorite($userId, $bookId);
        $result['favorite_count'] = $this->bookLikeService->getBookFavoriteCount($bookId);
        
        $this->json($result);
    }

    /**
     * 处理取消收藏
     */
    private function handleUnfavorite(): void
    {
        if (!AuthMiddleware::check()) {
            $this->json(['success' => false, 'message' => '请先登录'], 401);
            return;
        }

        $userId = $this->auth->getUserId();
        $bookId = (int)$this->post('book_id', 0);

        if ($bookId <= 0) {
            $this->json(['success' => false, 'message' => '无效的书籍ID'], 400);
            return;
        }

        $result = $this->bookLikeService->removeFavorite($userId, $bookId);
        $result['favorite_count'] = $this->bookLikeService->getBookFavoriteCount($bookId);
        
        $this->json($result);
    }

    /**
     * 处理忘记密码
     */
    private function handleForgotPassword(): void
    {
        // TODO: 实现忘记密码逻辑
        $this->showForgotPassword(['message' => '功能开发中']);
    }

    /**
     * 处理重置密码
     */
    private function handleResetPassword(): void
    {
        // TODO: 实现重置密码逻辑
        $this->showResetPassword(['error' => '功能开发中']);
    }

    /**
     * 处理更新个人资料
     */
    private function handleUpdateProfile(): void
    {
        AuthMiddleware::require('?action=login');
        
        $current_password = $this->post('current_password', '');
        $new_password = $this->post('new_password', '');
        $confirm_password = $this->post('confirm_password', '');
        $email = trim($this->post('email', ''));

        if (!$current_password) {
            $this->profile(['error' => '请输入当前密码']);
            return;
        }

        // 验证当前密码
        $stmt = $this->pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$this->auth->getUserId()]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($current_password, $user['password_hash'])) {
            $this->profile(['error' => '当前密码错误']);
            return;
        }

        $user_info = $this->auth->getUserInfo($this->auth->getUserId());
        $profile_message = '';
        $profile_error = '';

        // 更新邮箱（如果提供了新邮箱）
        if ($email && $email !== $user_info['email']) {
            $email = strtolower(trim($email));
            
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ? AND id != ?");
                $stmt->execute([$email, $this->auth->getUserId()]);
                if ($stmt->fetch()) {
                    $this->profile(['error' => '该邮箱已被其他用户使用']);
                    return;
                }
                
                if ($this->userService->updateEmail($this->auth->getUserId(), $email)) {
                    $profile_message = '邮箱更新成功。';
                } else {
                    $this->profile(['error' => '邮箱更新失败，请稍后再试']);
                    return;
                }
            } else {
                $this->profile(['error' => '请输入有效的邮箱地址']);
                return;
            }
        }

        // 更新密码（如果提供了新密码）
        if ($new_password) {
            $password_validation = $this->userService->validatePassword($new_password);
            if (!$password_validation['valid']) {
                $this->profile(['error' => $password_validation['message']]);
                return;
            }
            
            if ($new_password !== $confirm_password) {
                $this->profile(['error' => '新密码与确认密码不匹配']);
                return;
            }
            
            if ($this->userService->updatePassword($this->auth->getUserId(), $new_password)) {
                $profile_message = $profile_message ?: '密码更新成功。';
            } else {
                $this->profile(['error' => '密码更新失败，请稍后再试']);
                return;
            }
        }

        if ($profile_message) {
            $this->profile(['message' => $profile_message]);
        } else {
            $this->profile(['message' => '资料更新成功']);
        }
    }

    // ========== GET页面显示方法 ==========

    /**
     * 首页/搜索页
     */
    private function index(): void
    {
        $query = trim($this->get('q', ''));
        $type = $this->get('type', 'all');
        $format = $this->get('format', '');
        $page = $this->getPage();
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $book_layout = $_COOKIE['book_layout'] ?? 'grid';

        $books = [];
        $total = 0;
        $popular_searches = [];

        if (!empty($query)) {
            // 开始搜索书籍
            if (!empty($type)) {
                if ($type === 'all') {
                    $sql_where = "title like '%$query%' or author like '%$query%' or description like '%$query%'";
                }elseif ($type === 'title') {
                    $sql_where = "title like '%$query%'";
                }elseif ($type === 'author') {
                    $sql_where = "author like '%$query%'";
                }elseif ($type === 'description') {
                    $sql_where = "description like '%$query%'";
                }
            }
            $sql_where = "($sql_where)";
            if (!empty($format)) {
                $sql_where .= " and file_format = '$format'";
            }
           
            $sql = "select * from books where $sql_where order by id desc limit $limit offset $offset";   // 使用 LIKE 搜索
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $total = $this->pdo->query("select count(*) from books where $sql_where")->fetchColumn();
            // 记录搜索历史（只在第一页时记录）
            if ($page === 1) {
                $user_id = $this->auth->isLoggedIn() ? $this->auth->getUserId() : null;
                $networkHelper = new NetworkHelper();
                $ipAddress = $networkHelper->getClientIp();
                $this->searchService->logSearchHistory($query, $type, $total, $user_id, $ipAddress);
            }
        } else {
            // 没有搜索关键词时，显示随机50本带封面的书籍
            $books = $this->bookService->getRandomBooks(50);
            $total = count($books);
        }

        // 获取热门搜索关键词（在搜索页面和首页显示）
        if (empty($query)) {
            $popular_searches = $this->searchService->getPopularSearches(10);
        }

        $total_pages = !empty($query) ? ceil($total / $limit) : 1;

        $this->render('search', [
            'action' => 'search',
            'query' => $query,
            'type' => $type,
            'format' => $format,
            'books' => $books,
            'total' => $total,
            'page' => $page,
            'total_pages' => $total_pages,
            'limit' => $limit,
            'book_layout' => $book_layout,
            'popular_searches' => $popular_searches,
        ], 'layout');
    }

    /**
     * 显示登录页面
     */
    private function showLogin(array $data = []): void
    {
        $this->render('login', [
            'action' => 'login',
            'login_error' => $data['error'] ?? null,
            'register_success' => $data['success'] ?? null,
        ], 'layout');
    }

    /**
     * 显示注册页面
     */
    private function showRegister(array $data = []): void
    {
        $this->render('register', [
            'action' => 'register',
            'register_error' => $data['error'] ?? null,
        ], 'layout');
    }

    /**
     * 书籍详情页
     */
    private function detail(): void
    {
        $book_id = intval($this->get('id', 0));
        
        if (!$book_id) {
            $this->redirect('index.php');
            return;
        }

        $stmt = $this->pdo->prepare("
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
            $this->redirect('index.php');
            return;
        }

        // 获取相关推荐书籍
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM books
            WHERE id < ?
            ORDER BY id DESC
            LIMIT 10
        ");
        $stmt->execute([$book_id]);
        $related_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 获取点赞和收藏状态
        $book['like_count'] = $this->bookLikeService->getBookLikeCount($book_id);
        $book['favorite_count'] = $this->bookLikeService->getBookFavoriteCount($book_id);
        
        if ($this->auth->isLoggedIn()) {
            $user_id = $this->auth->getUserId();
            $book['is_liked'] = $this->bookLikeService->isBookLiked($user_id, $book_id);
            $book['is_favorited'] = $this->bookLikeService->isBookFavorited($user_id, $book_id);
        } else {
            $book['is_liked'] = false;
            $book['is_favorited'] = false;
        }

        $book_layout = $_COOKIE['book_layout'] ?? 'grid';

        $current_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $this->render('detail', [
            'action' => 'detail',
            'book' => $book,
            'related_books' => $related_books,
            'book_layout' => $book_layout,
            'pageTitle' => htmlspecialchars($book['title']) . ' - ' . htmlspecialchars($book['author']) . ' - 七秒电子书',
            'pageDescription' => htmlspecialchars(mb_substr(strip_tags($book['description'] ?? '暂无简介'), 0, 155)),
            'pageKeywords' => htmlspecialchars($book['title']) . ',' . htmlspecialchars($book['author']) . ',电子书下载,' . htmlspecialchars($book['category'] ?? '') . ',' . htmlspecialchars($book['file_format']) . '电子书,免费电子书',
        ], 'layout');
    }

    /**
     * 下载处理
     */
    private function download(): void
    {
        if (!$this->auth->isLoggedIn()) {
            $this->redirect('?action=login');
            return;
        }

        $book_id = intval($this->get('id', 0));
        $user_id = $this->auth->getUserId();
        
        if ($book_id <= 0) {
            $this->redirect('index.php?error=' . urlencode('无效的书籍ID'));
            return;
        }

        // 获取书籍信息
        $stmt = $this->pdo->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$book) {
            $this->redirect('index.php?error=' . urlencode('书籍不存在'));
            return;
        }

        // 使用原子操作尝试记录下载日志
        $networkHelper = new NetworkHelper();
        $ipAddress = $networkHelper->getClientIp();
        
        if (!$this->downloadService->tryLogDownload($user_id, $book_id, $ipAddress)) {
            $this->redirect('?action=detail&id=' . $book_id . '&error=' . urlencode('今日下载次数已达上限（5次），请明天再试'));
            return;
        }

        // 下载日志已成功记录，现在提供文件下载
        $filepath = __DIR__ . '/../../' . $book['file_path'];
        
        // 安全检查：确保文件路径在预期目录内
        $real_path = realpath($filepath);
        $base_dir = realpath(__DIR__ . '/../../');
        
        if (!$real_path || strpos($real_path, $base_dir) !== 0) {
            $this->redirect('?action=detail&id=' . $book_id . '&error=' . urlencode('文件路径无效'));
            return;
        }
        
        if (!file_exists($filepath)) {
            $this->redirect('?action=detail&id=' . $book_id . '&error=' . urlencode('文件不存在'));
            return;
        }

        // 清除任何之前的输出
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // 设置下载头
        $contentTypes = [
            'pdf' => 'application/pdf',
            'epub' => 'application/epub+zip',
            'mobi' => 'application/x-mobipocket-ebook',
            'azw3' => 'application/vnd.amazon.ebook',
            'djvu' => 'image/vnd.djvu',
            'txt' => 'text/plain; charset=utf-8',
            'zip' => 'application/zip',
            'rar' => 'application/vnd.rar',
        ];
        
        $fileFormat = strtolower($book['file_format']);
        header('Content-Type: ' . ($contentTypes[$fileFormat] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($book['file_name']) . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // 输出文件内容
        readfile($filepath);
        exit;
    }

    /**
     * 个人资料页
     */
    private function profile(array $data = []): void
    {
        AuthMiddleware::require('?action=login');
        
        $user_info = $this->auth->getUserInfo($this->auth->getUserId());
        $liked_count = $this->bookLikeService->getUserLikedBooksCount($this->auth->getUserId());
        $favorited_count = $this->bookLikeService->getUserFavoritedBooksCount($this->auth->getUserId());
        
        $this->render('profile', [
            'action' => 'profile',
            'user_info' => $user_info,
            'liked_count' => $liked_count,
            'favorited_count' => $favorited_count,
            'message' => $data['message'] ?? null,
            'error' => $data['error'] ?? null,
        ], 'layout');
    }

    /**
     * 我的点赞
     */
    private function myLikes(): void
    {
        AuthMiddleware::require('?action=login');
        
        $user_id = $this->auth->getUserId();
        $page = $this->getPage();
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $book_layout = $_COOKIE['book_layout'] ?? 'grid';
        
        $user_liked_total = $this->bookLikeService->getUserLikedBooksCount($user_id);
        $user_liked_books = $this->bookLikeService->getUserLikedBooks($user_id, $limit, $offset);
        $total_liked_pages = ceil($user_liked_total / $limit);

        $this->render('my_likes', [
            'action' => 'my_likes',
            'user_liked_books' => $user_liked_books,
            'user_liked_total' => $user_liked_total,
            'page' => $page,
            'total_liked_pages' => $total_liked_pages,
            'book_layout' => $book_layout,
        ], 'layout');
    }

    /**
     * 我的收藏
     */
    private function myFavorites(): void
    {
        AuthMiddleware::require('?action=login');
        
        $user_id = $this->auth->getUserId();
        $page = $this->getPage();
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $book_layout = $_COOKIE['book_layout'] ?? 'grid';
        
        $user_favorited_total = $this->bookLikeService->getUserFavoritedBooksCount($user_id);
        $user_favorited_books = $this->bookLikeService->getUserFavoritedBooks($user_id, $limit, $offset);
        $total_favorited_pages = ceil($user_favorited_total / $limit);

        $this->render('my_favorites', [
            'action' => 'my_favorites',
            'user_favorited_books' => $user_favorited_books,
            'user_favorited_total' => $user_favorited_total,
            'page' => $page,
            'total_favorited_pages' => $total_favorited_pages,
            'book_layout' => $book_layout,
        ], 'layout');
    }

    /**
     * 热门搜索
     */
    private function popularSearches(): void
    {
        $popular_searches_list = $this->searchService->getPopularSearches(50);

        $this->render('popular_searches', [
            'action' => 'popular_searches',
            'popular_searches_list' => $popular_searches_list,
        ], 'layout');
    }

    /**
     * 下载排行
     */
    private function downloadRanking(): void
    {
        $download_ranking_books = $this->bookService->getDownloadRanking();
        $book_layout = $_COOKIE['book_layout'] ?? 'grid';

        $this->render('download_ranking', [
            'action' => 'download_ranking',
            'download_ranking_books' => $download_ranking_books,
            'book_layout' => $book_layout,
        ], 'layout');
    }

    /**
     * 最近更新
     */
    private function latest(): void
    {
        $page = $this->getPage();
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $book_layout = $_COOKIE['book_layout'] ?? 'grid';
        
        $latest_books_total = $this->bookService->getTotalCount();
        $latest_books = $this->bookService->getLatestBooks($limit, $offset);
        $total_latest_pages = ceil($latest_books_total / $limit);

        $this->render('latest', [
            'action' => 'latest',
            'latest_books' => $latest_books,
            'latest_books_total' => $latest_books_total,
            'page' => $page,
            'total_latest_pages' => $total_latest_pages,
            'book_layout' => $book_layout,
        ], 'layout');
    }

    /**
     * 显示忘记密码页面
     */
    private function showForgotPassword(array $data = []): void
    {
        $this->render('forgot_password', [
            'action' => 'forgot_password',
            'forgot_password_message' => $data['message'] ?? null,
            'forgot_password_error' => $data['error'] ?? null,
        ], 'layout');
    }

    /**
     * 显示重置密码页面
     */
    private function showResetPassword(array $data = []): void
    {
        $token = $this->get('token', '');
        $reset_token = null;
        $reset_email = null;
        $reset_password_error = $data['error'] ?? null;
        $reset_password_success = $data['success'] ?? null;

        if ($token) {
            $stmt = $this->pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
            $reset_request = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($reset_request) {
                if (strtotime($reset_request['expires_at']) > time()) {
                    $reset_token = $token;
                    $reset_email = $reset_request['email'];
                } else {
                    $reset_password_error = '密码重置链接已过期，请重新申请。';
                    $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                    $stmt->execute([$token]);
                }
            } else {
                $reset_password_error = '无效的密码重置链接。';
            }
        } elseif (!$reset_password_error && !$reset_password_success) {
            $reset_password_error = '缺少必要的参数。';
        }

        $this->render('reset_password', [
            'action' => 'reset_password',
            'reset_token' => $reset_token,
            'reset_email' => $reset_email,
            'reset_password_error' => $reset_password_error,
            'reset_password_success' => $reset_password_success,
        ], 'layout');
    }

    /**
     * 生成 sitemap.xml 文件
     * 
     * @param bool $showMessage 是否显示成功消息（用于通过action访问时）
     * @return bool 是否成功生成
     */
    private function generateSitemap(bool $showMessage = false): bool
    {
        $sitemapPath = __DIR__ . '/../../sitemap.xml';
        
        try {
            // 获取基础URL
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = $protocol . '://' . $host;
            
            // 开始构建XML内容
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            
            // 添加主页
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($baseUrl . '/', ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "    <changefreq>daily</changefreq>\n";
            $xml .= "    <priority>1.0</priority>\n";
            $xml .= "  </url>\n";
            
            // 获取所有电子书信息
            $stmt = $this->pdo->query("SELECT id, updated_at, created_at FROM books ORDER BY id DESC LIMIT 10000");
            $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 为每本电子书添加URL条目
            foreach ($books as $book) {
                $url = $baseUrl . '/?action=detail&id=' . $book['id'];
                $lastmod = !empty($book['updated_at']) 
                    ? date('Y-m-d', strtotime($book['updated_at'])) 
                    : date('Y-m-d', strtotime($book['created_at']));
                
                $xml .= "  <url>\n";
                $xml .= "    <loc>" . htmlspecialchars($url, ENT_XML1, 'UTF-8') . "</loc>\n";
                $xml .= "    <lastmod>" . htmlspecialchars($lastmod, ENT_XML1, 'UTF-8') . "</lastmod>\n";
                $xml .= "    <changefreq>monthly</changefreq>\n";
                $xml .= "    <priority>0.8</priority>\n";
                $xml .= "  </url>\n";
            }
            
            // 结束标签
            $xml .= "</urlset>\n";
            
            // 写入文件
            if (file_put_contents($sitemapPath, $xml) === false) {
                throw new \RuntimeException('无法写入 sitemap.xml 文件，请检查文件权限');
            }
            
            // 如果通过 action 访问，返回成功消息
            if ($showMessage) {
                header('Content-Type: text/html; charset=utf-8');
                echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Sitemap生成成功</title></head><body>';
                echo '<h1>Sitemap生成成功</h1>';
                echo '<p>sitemap.xml 文件已生成到网站根目录。</p>';
                echo '<p><a href="/sitemap.xml">查看 sitemap.xml</a></p>';
                echo '</body></html>';
                exit;
            }
            
            return true;
        } catch (\Exception $e) {
            if ($showMessage) {
                header('Content-Type: text/html; charset=utf-8');
                http_response_code(500);
                echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Sitemap生成失败</title></head><body>';
                echo '<h1>Sitemap生成失败</h1>';
                echo '<p>错误信息：' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
                echo '</body></html>';
                exit;
            }
            return false;
        }
    }
}

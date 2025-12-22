<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use App\Services\BookService;
use App\Services\BookLikeService;
use App\Services\DownloadService;
use App\Services\SearchService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FrontController extends Controller
{
    protected BookService $bookService;
    protected BookLikeService $bookLikeService;
    protected DownloadService $downloadService;
    protected SearchService $searchService;
    protected UserService $userService;

    public function __construct(
        BookService $bookService,
        BookLikeService $bookLikeService,
        DownloadService $downloadService,
        SearchService $searchService,
        UserService $userService
    ) {
        $this->bookService = $bookService;
        $this->bookLikeService = $bookLikeService;
        $this->downloadService = $downloadService;
        $this->searchService = $searchService;
        $this->userService = $userService;
    }

    /**
     * 首页/搜索页
     */
    public function index(Request $request)
    {
        $query = trim($request->get('q', ''));
        $type = $request->get('type', 'all');
        $format = $request->get('format', '');
        $page = max(1, (int) $request->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $bookLayout = $request->cookie('book_layout', 'grid');

        $books = [];
        $total = 0;
        $popularSearches = [];

        if (!empty($query)) {
            $queryBuilder = Book::query();

            // 构建搜索条件
            if ($type === 'all') {
                $queryBuilder->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                      ->orWhere('author', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%");
                });
            } elseif ($type === 'title') {
                $queryBuilder->where('title', 'like', "%{$query}%");
            } elseif ($type === 'author') {
                $queryBuilder->where('author', 'like', "%{$query}%");
            } elseif ($type === 'description') {
                $queryBuilder->where('description', 'like', "%{$query}%");
            }

            if (!empty($format)) {
                $queryBuilder->where('file_format', $format);
            }

            $books = $queryBuilder->orderByDesc('id')
                ->limit($limit)
                ->offset($offset)
                ->get()
                ->toArray();

            $total = $queryBuilder->count();

            // 记录搜索历史（只在第一页时记录）
            if ($page === 1) {
                $userId = Auth::check() ? Auth::id() : null;
                $this->searchService->logSearchHistory($query, $type, $total, $userId, $request->ip());
            }
        } else {
            // 没有搜索关键词时，显示随机50本带封面的书籍
            $books = $this->bookService->getRandomBooks(48);
            $total = count($books);
        }

        // 获取热门搜索关键词（在搜索页面和首页显示）
        if (empty($query)) {
            $popularSearches = $this->searchService->getPopularSearches(10);
        }

        $totalPages = !empty($query) ? ceil($total / $limit) : 1;

        return view('search', [
            'action' => 'search',
            'query' => $query,
            'type' => $type,
            'format' => $format,
            'books' => $books,
            'total' => $total,
            'page' => $page,
            'total_pages' => $totalPages,
            'limit' => $limit,
            'book_layout' => $bookLayout,
            'popular_searches' => $popularSearches,
        ]);
    }

    /**
     * 书籍详情页
     */
    public function detail(Request $request, $id)
    {
        $bookId = (int) $id;

        if (!$bookId) {
            return redirect()->route('home');
        }

        $book = Book::with('tags')
            ->where('id', $bookId)
            ->first();

        if (!$book) {
            return redirect()->route('home');
        }

        // 获取相关推荐书籍
        $relatedBooks = Book::where('id', '<', $bookId)
            ->orderByDesc('id')
            ->limit(12)
            ->get()
            ->toArray();

        // 获取点赞和收藏状态
        $book->like_count = $this->bookLikeService->getBookLikeCount($bookId);
        $book->favorite_count = $this->bookLikeService->getBookFavoriteCount($bookId);

        if (Auth::check()) {
            $userId = Auth::id();
            $book->is_liked = $this->bookLikeService->isBookLiked($userId, $bookId);
            $book->is_favorited = $this->bookLikeService->isBookFavorited($userId, $bookId);
        } else {
            $book->is_liked = false;
            $book->is_favorited = false;
        }

        $bookLayout = $request->cookie('book_layout', 'grid');

        return view('detail', [
            'action' => 'detail',
            'book' => $book,
            'related_books' => $relatedBooks,
            'book_layout' => $bookLayout,
            'pageTitle' => htmlspecialchars($book->title) . ' - ' . $book->author . ' - 七秒电子书',
            'pageDescription' => htmlspecialchars(strip_tags($book->description ?? '暂无简介')),
            'pageKeywords' => htmlspecialchars($book->title) . ',' . $book->author . ',电子书下载,' . $book->file_format . '电子书,免费电子书,七秒电子书,电子书资源,电子书下载网站,七秒书盘',
        ]);
    }

    /**
     * 下载处理
     */
    public function download(Request $request, $id)
    {
        if (!Auth::check()) {
            // 保存当前详情页URL到session，登录后返回
            $bookId = (int) $id;
            if ($bookId > 0) {
                session(['intended_url' => route('book.detail', $bookId)]);
            }
            return redirect()->route('login');
        }

        $bookId = (int) $id;
        $userId = Auth::id();

        if ($bookId <= 0) {
            return redirect()->route('home')->with('error', '无效的书籍ID');
        }

        // 获取书籍信息
        $book = Book::find($bookId);

        if (!$book) {
            return redirect()->route('home')->with('error', '书籍不存在');
        }

        // 使用原子操作尝试记录下载日志
        if (!$this->downloadService->tryLogDownload($userId, $bookId)) {
            return redirect()->route('book.detail', $bookId)->with('error', '今日下载次数已达上限（5次），请明天再试');
        }

        // 下载日志已成功记录，现在提供文件下载
        $filepath = Storage::disk('local')->path($book->file_path);


        if (!file_exists($filepath)) {
            return redirect()->route('book.detail', $bookId)->with('error', '文件不存在');
        }

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

        $fileFormat = strtolower($book->file_format);
        $contentType = $contentTypes[$fileFormat] ?? 'application/octet-stream';

        return response()->download($filepath, $book->file_name, [
            'Content-Type' => $contentType,
        ]);
    }

    /**
     * 显示登录页面
     */
    public function showLogin(Request $request)
    {
        // 如果URL中有intended参数，保存到session
        $intended = $request->get('intended');
        if ($intended) {
            session(['intended_url' => $intended]);
        }
        
        return view('login', [
            'action' => 'login',
        ]);
    }

    /**
     * 处理登录
     */
    public function handleLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = trim($request->input('username'));
        $password = $request->input('password');

        $user = User::where('username', $username)->first();

        if ($user && Hash::check($password, $user->password_hash)) {
            Auth::login($user);
            
            // 检查是否有保存的intended URL，如果有则重定向到那里
            $intendedUrl = session('intended_url');
            if ($intendedUrl) {
                session()->forget('intended_url');
                return redirect($intendedUrl);
            }
            
            return redirect()->route('home');
        }

        return redirect()->route('login')->with('error', '用户名或密码错误');
    }

    /**
     * 处理登出
     */
    public function logout()
    {
        Auth::logout();
        return redirect()->route('home');
    }

    /**
     * 显示注册页面
     */
    public function showRegister(Request $request)
    {
        // 如果URL中有intended参数，保存到session
        $intended = $request->get('intended');
        if ($intended) {
            session(['intended_url' => $intended]);
        }
        
        return view('register', [
            'action' => 'register',
        ]);
    }

    /**
     * 处理注册
     */
    public function handleRegister(Request $request)
    {
        $request->validate([
            'reg_username' => 'required|string',
            'reg_password' => 'required|string',
            'reg_email' => 'required|email',
        ]);

        $username = trim($request->input('reg_username'));
        $password = $request->input('reg_password');
        $email = strtolower(trim($request->input('reg_email')));

        // 验证用户名
        $usernameValidation = $this->userService->validateUsername($username);
        if (!$usernameValidation['valid']) {
            return redirect()->route('register')->with('error', $usernameValidation['message']);
        }

        // 验证密码
        $passwordValidation = $this->userService->validatePassword($password);
        if (!$passwordValidation['valid']) {
            return redirect()->route('register')->with('error', $passwordValidation['message']);
        }

        // 检查用户名是否已存在
        if (User::where('username', $username)->exists()) {
            return redirect()->route('register')->with('error', '用户名已存在');
        }

        // 检查邮箱是否已存在
        if (User::where('email', $email)->exists()) {
            return redirect()->route('register')->with('error', '该邮箱已被注册');
        }

        // 创建新用户
        $user = User::create([
            'username' => $username,
            'password_hash' => Hash::make($password),
            'email' => $email,
            'role' => 'user',
        ]);

        Auth::login($user);

        // 检查是否有保存的intended URL，如果有则重定向到那里
        $intendedUrl = session('intended_url');
        if ($intendedUrl) {
            session()->forget('intended_url');
            return redirect($intendedUrl);
        }

        return redirect()->route('home');
    }

    /**
     * 个人资料页
     */
    public function profile()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $likedCount = $this->bookLikeService->getUserLikedBooksCount($user->id);
        $favoritedCount = $this->bookLikeService->getUserFavoritedBooksCount($user->id);

        return view('profile', [
            'action' => 'profile',
            'user_info' => $user,
            'liked_count' => $likedCount,
            'favorited_count' => $favoritedCount,
        ]);
    }

    /**
     * 处理更新个人资料
     */
    public function updateProfile(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $currentPassword = $request->input('current_password', '');
        $newPassword = $request->input('new_password', '');
        $confirmPassword = $request->input('confirm_password', '');
        $email = trim($request->input('email', ''));

        if (!$currentPassword) {
            return redirect()->route('profile')->with('error', '请输入当前密码');
        }

        // 验证当前密码
        if (!Hash::check($currentPassword, $user->password_hash)) {
            return redirect()->route('profile')->with('error', '当前密码错误');
        }

        $message = '';

        // 更新邮箱（如果提供了新邮箱）
        if ($email && $email !== $user->email) {
            $email = strtolower(trim($email));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return redirect()->route('profile')->with('error', '请输入有效的邮箱地址');
            }

            if (User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
                return redirect()->route('profile')->with('error', '该邮箱已被其他用户使用');
            }

            if ($this->userService->updateEmail($user->id, $email)) {
                $message = '邮箱更新成功。';
            } else {
                return redirect()->route('profile')->with('error', '邮箱更新失败，请稍后再试');
            }
        }

        // 更新密码（如果提供了新密码）
        if ($newPassword) {
            $passwordValidation = $this->userService->validatePassword($newPassword);
            if (!$passwordValidation['valid']) {
                return redirect()->route('profile')->with('error', $passwordValidation['message']);
            }

            if ($newPassword !== $confirmPassword) {
                return redirect()->route('profile')->with('error', '新密码与确认密码不匹配');
            }

            if ($this->userService->updatePassword($user->id, $newPassword)) {
                $message = $message ?: '密码更新成功。';
            } else {
                return redirect()->route('profile')->with('error', '密码更新失败，请稍后再试');
            }
        }

        return redirect()->route('profile')->with('message', $message ?: '资料更新成功');
    }

    /**
     * 我的点赞
     */
    public function myLikes(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userId = Auth::id();
        $page = max(1, (int) $request->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $bookLayout = $request->cookie('book_layout', 'grid');

        $userLikedTotal = $this->bookLikeService->getUserLikedBooksCount($userId);
        $userLikedBooks = $this->bookLikeService->getUserLikedBooks($userId, $limit, $offset);
        $totalLikedPages = ceil($userLikedTotal / $limit);

        return view('my_likes', [
            'action' => 'my_likes',
            'user_liked_books' => $userLikedBooks,
            'user_liked_total' => $userLikedTotal,
            'page' => $page,
            'total_liked_pages' => $totalLikedPages,
            'book_layout' => $bookLayout,
        ]);
    }

    /**
     * 我的收藏
     */
    public function myFavorites(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userId = Auth::id();
        $page = max(1, (int) $request->get('page', 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $bookLayout = $request->cookie('book_layout', 'grid');

        $userFavoritedTotal = $this->bookLikeService->getUserFavoritedBooksCount($userId);
        $userFavoritedBooks = $this->bookLikeService->getUserFavoritedBooks($userId, $limit, $offset);
        $totalFavoritedPages = ceil($userFavoritedTotal / $limit);

        return view('my_favorites', [
            'action' => 'my_favorites',
            'user_favorited_books' => $userFavoritedBooks,
            'user_favorited_total' => $userFavoritedTotal,
            'page' => $page,
            'total_favorited_pages' => $totalFavoritedPages,
            'book_layout' => $bookLayout,
        ]);
    }

    /**
     * 热门搜索
     */
    public function popularSearches()
    {
        $popularSearchesList = $this->searchService->getPopularSearches(50);

        return view('popular_searches', [
            'action' => 'popular_searches',
            'popular_searches_list' => $popularSearchesList,
        ]);
    }

    /**
     * 下载排行
     */
    public function downloadRanking(Request $request)
    {
        $downloadRankingBooks = $this->bookService->getDownloadRanking();
        $bookLayout = $request->cookie('book_layout', 'grid');

        return view('download_ranking', [
            'action' => 'download_ranking',
            'download_ranking_books' => $downloadRankingBooks,
            'book_layout' => $bookLayout,
        ]);
    }

    /**
     * 最近更新
     */
    public function latest(Request $request)
    {
        $page = max(1, (int) $request->get('page', 1));
        $limit = 24;
        $offset = ($page - 1) * $limit;
        $bookLayout = $request->cookie('book_layout', 'grid');

        $latestBooksTotal = $this->bookService->getTotalCount();
        $latestBooks = $this->bookService->getLatestBooks($limit, $offset);
        $totalLatestPages = ceil($latestBooksTotal / $limit);

        return view('latest', [
            'action' => 'latest',
            'latest_books' => $latestBooks,
            'latest_books_total' => $latestBooksTotal,
            'page' => $page,
            'total_latest_pages' => $totalLatestPages,
            'book_layout' => $bookLayout,
        ]);
    }

    /**
     * 处理点赞
     */
    public function like(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => '请先登录'], 401);
        }

        $userId = Auth::id();
        $bookId = (int) $request->input('book_id', 0);

        if ($bookId <= 0) {
            return response()->json(['success' => false, 'message' => '无效的书籍ID'], 400);
        }

        $result = $this->bookLikeService->addLike($userId, $bookId);
        $result['like_count'] = $this->bookLikeService->getBookLikeCount($bookId);

        return response()->json($result);
    }

    /**
     * 处理取消点赞
     */
    public function unlike(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => '请先登录'], 401);
        }

        $userId = Auth::id();
        $bookId = (int) $request->input('book_id', 0);

        if ($bookId <= 0) {
            return response()->json(['success' => false, 'message' => '无效的书籍ID'], 400);
        }

        $result = $this->bookLikeService->removeLike($userId, $bookId);
        $result['like_count'] = $this->bookLikeService->getBookLikeCount($bookId);

        return response()->json($result);
    }

    /**
     * 处理收藏
     */
    public function favorite(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => '请先登录'], 401);
        }

        $userId = Auth::id();
        $bookId = (int) $request->input('book_id', 0);

        if ($bookId <= 0) {
            return response()->json(['success' => false, 'message' => '无效的书籍ID'], 400);
        }

        $result = $this->bookLikeService->addFavorite($userId, $bookId);
        $result['favorite_count'] = $this->bookLikeService->getBookFavoriteCount($bookId);

        return response()->json($result);
    }

    /**
     * 处理取消收藏
     */
    public function unfavorite(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => '请先登录'], 401);
        }

        $userId = Auth::id();
        $bookId = (int) $request->input('book_id', 0);

        if ($bookId <= 0) {
            return response()->json(['success' => false, 'message' => '无效的书籍ID'], 400);
        }

        $result = $this->bookLikeService->removeFavorite($userId, $bookId);
        $result['favorite_count'] = $this->bookLikeService->getBookFavoriteCount($bookId);

        return response()->json($result);
    }

    /**
     * 显示忘记密码页面
     */
    public function showForgotPassword()
    {
        return view('forgot_password', [
            'action' => 'forgot_password',
        ]);
    }

    /**
     * 处理忘记密码
     */
    public function handleForgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($request->input('email')));

        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('forgot_password')->with('error', '该邮箱未注册');
        }

        // 生成重置令牌
        $token = bin2hex(random_bytes(32));
        $expiresAt = now()->addHours(24); // 24小时后过期

        // 删除旧的令牌
        DB::table('password_resets')->where('email', $email)->delete();

        // 插入新令牌
        DB::table('password_resets')->insert([
            'email' => $email,
            'token' => $token,
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 生成重置链接
        $resetUrl = route('reset_password', ['token' => $token]);

        // TODO: 发送邮件（这里暂时只显示消息和链接）
        // 实际部署时应该通过邮件发送链接，而不是在页面上显示
        return redirect()->route('forgot_password')->with('message', '密码重置链接已生成，请查看您的邮箱（功能开发中，重置链接：' . $resetUrl . '）');
    }

    /**
     * 显示重置密码页面
     */
    public function showResetPassword(Request $request)
    {
        $token = $request->get('token', '');
        $resetToken = null;
        $resetEmail = null;
        $error = null;

        if ($token) {
            $resetRequest = DB::table('password_resets')
                ->where('token', $token)
                ->first();

            if ($resetRequest) {
                if (strtotime($resetRequest->expires_at) > time()) {
                    $resetToken = $token;
                    $resetEmail = $resetRequest->email;
                } else {
                    $error = '密码重置链接已过期，请重新申请。';
                    DB::table('password_resets')->where('token', $token)->delete();
                }
            } else {
                $error = '无效的密码重置链接。';
            }
        } else {
            $error = '缺少必要的参数。';
        }

        return view('reset_password', [
            'action' => 'reset_password',
            'reset_token' => $resetToken,
            'reset_email' => $resetEmail,
            'reset_password_error' => $error,
            'reset_password_success' => $request->get('success'),
        ]);
    }

    /**
     * 处理重置密码
     */
    public function handleResetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|string|same:password',
        ]);

        $token = $request->input('token');
        $email = strtolower(trim($request->input('email')));
        $password = $request->input('password');

        $resetRequest = DB::table('password_resets')
            ->where('token', $token)
            ->where('email', $email)
            ->first();

        if (!$resetRequest) {
            return redirect()->route('reset_password', ['token' => $token])->with('error', '无效的密码重置链接');
        }

        if (strtotime($resetRequest->expires_at) <= time()) {
            DB::table('password_resets')->where('token', $token)->delete();
            return redirect()->route('reset_password', ['token' => $token])->with('error', '密码重置链接已过期，请重新申请');
        }

        // 验证密码
        $passwordValidation = $this->userService->validatePassword($password);
        if (!$passwordValidation['valid']) {
            return redirect()->route('reset_password', ['token' => $token])->with('error', $passwordValidation['message']);
        }

        // 更新密码
        $user = User::where('email', $email)->first();
        if ($user) {
            $user->password_hash = Hash::make($password);
            $user->save();

            // 删除重置令牌
            DB::table('password_resets')->where('token', $token)->delete();

            return redirect()->route('login')->with('message', '密码重置成功，请登录');
        }

        return redirect()->route('reset_password', ['token' => $token])->with('error', '用户不存在');
    }

    /**
     * 生成 sitemap.xml 文件
     */
    public function generateSitemap()
    {
        $sitemapPath = public_path('sitemap.xml');

        try {
            // 获取基础URL
            $baseUrl = url('/');

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
            $books = Book::select('id', 'updated_at', 'created_at')
                ->orderByDesc('id')
                ->limit(10000)
                ->get();

            // 为每本电子书添加URL条目
            foreach ($books as $book) {
                $url = $baseUrl . '/book/' . $book->id;
                $lastmod = $book->updated_at ? $book->updated_at->format('Y-m-d') : $book->created_at->format('Y-m-d');

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
            // file_put_contents($sitemapPath, $xml);

            return response($xml, 200)
                ->header('Content-Type', 'application/xml; charset=utf-8');
        } catch (\Exception $e) {
            return response('生成 sitemap 失败: ' . $e->getMessage(), 500);
        }
    }
}

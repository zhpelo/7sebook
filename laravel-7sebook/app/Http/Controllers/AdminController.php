<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Book;
use App\Models\BookTag;
use App\Models\DownloadLog;
use App\Models\SearchHistory;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    protected UserService $userService;
    protected const LIMIT = 20;

    public function __construct(UserService $userService)
    {
        $this->middleware('admin');
        $this->userService = $userService;
    }

    /**
     * 仪表盘
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_books' => Book::count(),
            'total_downloads' => DownloadLog::count(),
            'today_downloads' => DownloadLog::whereDate('download_time', today())->count(),
        ];

        // 获取近7天用户增长数据
        $userGrowthData = User::selectRaw('DATE(created_at) as date, COUNT(*) as user_count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $dates = [];
        $dailyUserCounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dates[] = $date;
            $dailyUserCounts[$date] = 0;
        }

        foreach ($userGrowthData as $data) {
            $dailyUserCounts[$data->date] = (int) $data->user_count;
        }

        // 获取近7天电子书增长数据
        $bookGrowthData = Book::selectRaw('DATE(created_at) as date, COUNT(*) as book_count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $dailyBookCounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dailyBookCounts[$date] = 0;
        }

        foreach ($bookGrowthData as $data) {
            $dailyBookCounts[$data->date] = (int) $data->book_count;
        }

        return view('admin.dashboard', [
            'action' => 'dashboard',
            'pageTitle' => '仪表盘 - 七秒电子书后台管理',
            'stats' => $stats,
            'dates' => $dates,
            'daily_user_counts' => $dailyUserCounts,
            'daily_book_counts' => $dailyBookCounts,
        ]);
    }

    /**
     * 用户管理
     */
    public function users(Request $request)
    {
        $userSearch = trim($request->get('search', ''));
        $page = max(1, (int) $request->get('page', 1));
        $limit = self::LIMIT;
        $offset = ($page - 1) * $limit;

        $query = User::query();

        if (!empty($userSearch)) {
            $query->where(function ($q) use ($userSearch) {
                $q->where('username', 'like', "%{$userSearch}%")
                  ->orWhere('email', 'like', "%{$userSearch}%");
            });
        }

        $users = $query->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $totalUsers = $query->count();
        $totalPages = ceil($totalUsers / $limit);

        return view('admin.users', [
            'action' => 'users',
            'pageTitle' => '用户管理 - 七秒电子书后台管理',
            'users' => $users,
            'user_search' => $userSearch,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_users' => $totalUsers,
            'limit' => $limit,
            'message' => $request->get('message'),
            'error' => $request->get('error'),
        ]);
    }

    /**
     * 添加用户页面
     */
    public function showAddUser()
    {
        return view('admin.add_user', [
            'action' => 'add_user',
            'pageTitle' => '添加用户 - 七秒电子书后台管理',
            'error' => request()->get('error'),
        ]);
    }

    /**
     * 处理添加用户
     */
    public function postAddUser(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'email' => 'required|email',
            'role' => 'required|in:user,admin',
        ]);

        $username = trim($request->input('username'));
        $password = $request->input('password');
        $email = strtolower(trim($request->input('email')));
        $role = $request->input('role', 'user');

        // 验证用户名
        $usernameValidation = $this->userService->validateUsername($username);
        if (!$usernameValidation['valid']) {
            return redirect()->route('admin.add_user')->with('error', $usernameValidation['message']);
        }

        // 验证密码
        $passwordValidation = $this->userService->validatePassword($password);
        if (!$passwordValidation['valid']) {
            return redirect()->route('admin.add_user')->with('error', $passwordValidation['message']);
        }

        // 检查用户名是否已存在
        if (User::where('username', $username)->exists()) {
            return redirect()->route('admin.add_user')->with('error', '用户名已存在');
        }

        // 检查邮箱是否已存在
        if (User::where('email', $email)->exists()) {
            return redirect()->route('admin.add_user')->with('error', '该邮箱已被注册');
        }

        User::create([
            'username' => $username,
            'password_hash' => Hash::make($password),
            'email' => $email,
            'role' => $role,
        ]);

        return redirect()->route('admin.users')->with('message', '用户添加成功');
    }

    /**
     * 编辑用户页面
     */
    public function showEditUser($id)
    {
        $user = User::findOrFail($id);

        return view('admin.edit_user', [
            'action' => 'edit_user',
            'pageTitle' => '编辑用户 - 七秒电子书后台管理',
            'edit_user' => $user,
            'error' => request()->get('error'),
        ]);
    }

    /**
     * 处理编辑用户
     */
    public function postEditUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'username' => 'required|string',
            'email' => 'required|email',
            'role' => 'required|in:user,admin',
            'password' => 'nullable|string',
        ]);

        $username = trim($request->input('username'));
        $email = strtolower(trim($request->input('email')));
        $role = $request->input('role', 'user');
        $newPassword = $request->input('password');

        // 验证用户名
        $usernameValidation = $this->userService->validateUsername($username);
        if (!$usernameValidation['valid']) {
            return redirect()->route('admin.edit_user', $id)->with('error', $usernameValidation['message']);
        }

        // 检查用户名是否已被其他用户使用
        if (User::where('username', $username)->where('id', '!=', $id)->exists()) {
            return redirect()->route('admin.edit_user', $id)->with('error', '用户名已被其他用户使用');
        }

        // 检查邮箱是否已被其他用户使用
        if (User::where('email', $email)->where('id', '!=', $id)->exists()) {
            return redirect()->route('admin.edit_user', $id)->with('error', '该邮箱已被其他用户使用');
        }

        $user->username = $username;
        $user->email = $email;
        $user->role = $role;

        if ($newPassword) {
            $passwordValidation = $this->userService->validatePassword($newPassword);
            if (!$passwordValidation['valid']) {
                return redirect()->route('admin.edit_user', $id)->with('error', $passwordValidation['message']);
            }
            $user->password_hash = Hash::make($newPassword);
        }

        $user->save();

        return redirect()->route('admin.users')->with('message', '用户信息更新成功');
    }

    /**
     * 删除用户
     */
    public function deleteUser(Request $request, $id)
    {
        $confirm = $request->get('confirm');

        if ($confirm === 'yes') {
            User::findOrFail($id)->delete();
            return redirect()->route('admin.users')->with('message', '用户删除成功');
        }

        $user = User::findOrFail($id);

        return view('admin.delete_user', [
            'action' => 'delete_user',
            'pageTitle' => '删除用户 - 七秒电子书后台管理',
            'delete_user' => $user,
        ]);
    }

    /**
     * 批量删除用户
     */
    public function batchDeleteUsers(Request $request)
    {
        $ids = $request->get('ids');
        $confirm = $request->get('confirm');

        if ($ids && $confirm === 'yes') {
            $idArray = array_filter(array_map('intval', explode(',', $ids)));
            if (!empty($idArray)) {
                User::whereIn('id', $idArray)->delete();
                return redirect()->route('admin.users')->with('message', '已成功删除 ' . count($idArray) . ' 个用户');
            }
        }

        return redirect()->route('admin.users');
    }

    // 书籍管理、标签管理、下载记录、搜索历史等功能类似实现...
    // 由于篇幅限制，这里只展示核心功能，其他功能可以按照相同模式实现

    /**
     * 书籍管理
     */
    public function books(Request $request)
    {
        $bookSearch = trim($request->get('search', ''));
        $page = max(1, (int) $request->get('page', 1));
        $limit = self::LIMIT;
        $offset = ($page - 1) * $limit;

        $query = Book::with('tags');

        if (!empty($bookSearch)) {
            $query->where(function ($q) use ($bookSearch) {
                $q->where('title', 'like', "%{$bookSearch}%")
                  ->orWhere('author', 'like', "%{$bookSearch}%");
            });
        }

        $books = $query->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $totalBooks = $query->count();
        $totalPages = ceil($totalBooks / $limit);

        return view('admin.books', [
            'action' => 'books',
            'pageTitle' => '书籍管理 - 七秒电子书后台管理',
            'books' => $books,
            'book_search' => $bookSearch,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_books' => $totalBooks,
            'limit' => $limit,
            'message' => $request->get('message'),
            'error' => $request->get('error'),
        ]);
    }

    /**
     * 添加书籍页面
     */
    public function showAddBook()
    {
        return view('admin.add_book', [
            'action' => 'add_book',
            'pageTitle' => '添加电子书 - 七秒电子书后台管理',
            'error' => request()->get('error'),
        ]);
    }

    /**
     * 处理添加书籍
     */
    public function postAddBook(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'author' => 'required|string',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'publisher' => 'nullable|string',
            'page_count' => 'nullable|integer',
            'file_name' => 'nullable|string',
            'file_path' => 'nullable|string',
            'file_size' => 'nullable|string',
            'file_format' => 'nullable|string',
            'tags' => 'nullable|string',
        ]);

        $book = Book::create([
            'title' => trim($request->input('title')),
            'author' => trim($request->input('author')),
            'description' => trim($request->input('description', '')),
            'category' => trim($request->input('category', '')),
            'publisher' => trim($request->input('publisher', '')),
            'page_count' => (int) $request->input('page_count', 0),
            'file_name' => trim($request->input('file_name', '')),
            'file_path' => trim($request->input('file_path', '')),
            'file_size' => trim($request->input('file_size', '')),
            'file_format' => trim($request->input('file_format', '')),
        ]);

        // 处理标签
        $tags = trim($request->input('tags', ''));
        if ($tags) {
            $tagList = array_filter(array_map('trim', explode(',', $tags)));
            foreach ($tagList as $tag) {
                if ($tag) {
                    BookTag::create([
                        'book_id' => $book->id,
                        'tag_name' => $tag,
                    ]);
                }
            }
        }

        return redirect()->route('admin.books')->with('message', '电子书添加成功');
    }

    /**
     * 编辑书籍页面
     */
    public function showEditBook($id)
    {
        $book = Book::with('tags')->findOrFail($id);
        return view('admin.edit_book', [
            'action' => 'edit_book',
            'pageTitle' => '编辑电子书 - 七秒电子书后台管理',
            'book' => $book,
            'error' => request()->get('error'),
        ]);
    }

    /**
     * 处理编辑书籍
     */
    public function postEditBook(Request $request, $id)
    {
        $book = Book::findOrFail($id);

        $request->validate([
            'title' => 'required|string',
            'author' => 'required|string',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'publisher' => 'nullable|string',
            'page_count' => 'nullable|integer',
            'file_name' => 'nullable|string',
            'file_path' => 'nullable|string',
            'file_size' => 'nullable|string',
            'file_format' => 'nullable|string',
            'tags' => 'nullable|string',
        ]);

        $book->title = trim($request->input('title'));
        $book->author = trim($request->input('author'));
        $book->description = trim($request->input('description', ''));
        $book->category = trim($request->input('category', ''));
        $book->publisher = trim($request->input('publisher', ''));
        $book->page_count = (int) $request->input('page_count', 0);
        $book->file_name = trim($request->input('file_name', ''));
        $book->file_path = trim($request->input('file_path', ''));
        $book->file_size = trim($request->input('file_size', ''));
        $book->file_format = trim($request->input('file_format', ''));
        $book->save();

        // 删除旧标签
        BookTag::where('book_id', $book->id)->delete();

        // 添加新标签
        $tags = trim($request->input('tags', ''));
        if ($tags) {
            $tagList = array_filter(array_map('trim', explode(',', $tags)));
            foreach ($tagList as $tag) {
                if ($tag) {
                    BookTag::create([
                        'book_id' => $book->id,
                        'tag_name' => $tag,
                    ]);
                }
            }
        }

        return redirect()->route('admin.books')->with('message', '电子书信息更新成功');
    }

    /**
     * 删除书籍
     */
    public function deleteBook(Request $request, $id)
    {
        $confirm = $request->get('confirm');

        if ($confirm === 'yes') {
            Book::findOrFail($id)->delete();
            return redirect()->route('admin.books')->with('message', '电子书删除成功');
        }

        $book = Book::findOrFail($id);
        return view('admin.delete_book', [
            'action' => 'delete_book',
            'pageTitle' => '删除电子书 - 七秒电子书后台管理',
            'delete_book' => $book,
        ]);
    }

    /**
     * 批量删除书籍
     */
    public function batchDeleteBooks(Request $request)
    {
        $ids = $request->get('ids');
        $confirm = $request->get('confirm');

        if ($ids && $confirm === 'yes') {
            $idArray = array_filter(array_map('intval', explode(',', $ids)));
            if (!empty($idArray)) {
                Book::whereIn('id', $idArray)->delete();
                return redirect()->route('admin.books')->with('message', '已成功删除 ' . count($idArray) . ' 本电子书');
            }
        }

        return redirect()->route('admin.books');
    }

    /**
     * 下载记录管理
     */
    public function downloads(Request $request)
    {
        $page = max(1, (int) $request->get('page', 1));
        $limit = self::LIMIT;
        $offset = ($page - 1) * $limit;

        $downloads = DownloadLog::with(['user', 'book'])
            ->orderByDesc('download_time')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $totalDownloads = DownloadLog::count();
        $totalPages = ceil($totalDownloads / $limit);

        // 获取近28天下载趋势数据
        $downloadTrendData = DownloadLog::selectRaw('DATE(download_time) as date, COUNT(*) as download_count')
            ->where('download_time', '>=', now()->subDays(28))
            ->groupBy(DB::raw('DATE(download_time)'))
            ->orderBy('date')
            ->get();

        $dailyDownloadCounts = [];
        $downloadDates = [];
        for ($i = 27; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $downloadDates[] = $date;
            $dailyDownloadCounts[$date] = 0;
        }

        foreach ($downloadTrendData as $data) {
            $dailyDownloadCounts[$data->date] = (int) $data->download_count;
        }

        // 获取下载最多的10本书
        $topBooks = Book::select('books.id', 'books.title', 'books.author', DB::raw('COUNT(download_logs.id) as download_count'))
            ->join('download_logs', 'books.id', '=', 'download_logs.book_id')
            ->groupBy('books.id', 'books.title', 'books.author')
            ->orderByDesc('download_count')
            ->limit(10)
            ->get();

        return view('admin.downloads', [
            'action' => 'downloads',
            'pageTitle' => '下载记录 - 七秒电子书后台管理',
            'downloads' => $downloads,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_downloads' => $totalDownloads,
            'limit' => $limit,
            'download_dates' => $downloadDates,
            'daily_download_counts' => $dailyDownloadCounts,
            'top_books' => $topBooks,
        ]);
    }

    /**
     * 搜索历史管理
     */
    public function searchHistory(Request $request)
    {
        $searchKeyword = trim($request->get('search', ''));
        $page = max(1, (int) $request->get('page', 1));
        $limit = self::LIMIT;
        $offset = ($page - 1) * $limit;

        $query = SearchHistory::with('user');

        if (!empty($searchKeyword)) {
            $query->where('search_query', 'like', "%{$searchKeyword}%");
        }

        $searchRecords = $query->orderByDesc('search_time')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $totalSearchRecords = $query->count();
        $totalPages = ceil($totalSearchRecords / $limit);

        // 获取热门搜索
        $popularSearches = SearchHistory::select('search_query', DB::raw('COUNT(*) as search_count'), DB::raw('COUNT(DISTINCT user_id) as user_count'), DB::raw('COUNT(DISTINCT ip_address) as ip_count'))
            ->where('search_query', '!=', '')
            ->groupBy('search_query')
            ->orderByDesc('search_count')
            ->limit(20)
            ->get();

        return view('admin.search_history', [
            'action' => 'search_history',
            'pageTitle' => '搜索记录 - 七秒电子书后台管理',
            'search_records' => $searchRecords,
            'search_keyword' => $searchKeyword,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_search_records' => $totalSearchRecords,
            'limit' => $limit,
            'popular_searches' => $popularSearches,
        ]);
    }

    /**
     * 标签管理
     */
    public function tags(Request $request)
    {
        $tagSearch = trim($request->get('search', ''));
        $page = max(1, (int) $request->get('page', 1));
        $limit = self::LIMIT;
        $offset = ($page - 1) * $limit;

        // 使用原生SQL查询，因为GROUP_CONCAT在Laravel中处理较复杂
        $whereClause = '';
        $params = [];
        if (!empty($tagSearch)) {
            $whereClause = 'WHERE bt.tag_name LIKE ?';
            $params[] = "%{$tagSearch}%";
        }

        $sql = "
            SELECT 
                bt.tag_name,
                COUNT(DISTINCT bt.book_id) as usage_count
            FROM book_tags bt
            {$whereClause}
            GROUP BY bt.tag_name
            ORDER BY usage_count DESC, bt.tag_name ASC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;

        $tags = DB::select($sql, $params);
        $tags = array_map(function($tag) {
            return (array) $tag;
        }, $tags);

        // 获取总数
        $countSql = "SELECT COUNT(DISTINCT tag_name) as total FROM book_tags {$whereClause}";
        $countParams = !empty($tagSearch) ? ["%{$tagSearch}%"] : [];
        $totalTags = (int) (DB::selectOne($countSql, $countParams)->total ?? 0);
        $totalPages = ceil($totalTags / $limit);

        return view('admin.tags', [
            'action' => 'tags',
            'pageTitle' => '标签管理 - 七秒电子书后台管理',
            'tags' => $tags,
            'tag_search' => $tagSearch,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_tags' => $totalTags,
            'limit' => $limit,
            'message' => $request->get('message'),
            'error' => $request->get('error'),
        ]);
    }

    /**
     * 添加标签页面
     */
    public function showAddTag()
    {
        return view('admin.add_tag', [
            'action' => 'add_tag',
            'pageTitle' => '添加标签 - 七秒电子书后台管理',
            'error' => request()->get('error'),
        ]);
    }

    /**
     * 处理添加标签
     */
    public function postAddTag(Request $request)
    {
        $request->validate([
            'tag_name' => 'required|string',
            'auto_search' => 'nullable|boolean',
            'book_id' => 'nullable|integer',
        ]);

        $tagName = trim($request->input('tag_name'));
        $autoSearch = $request->has('auto_search') && $request->input('auto_search') == '1';
        $bookId = (int) $request->input('book_id', 0);

        $addedCount = 0;

        if ($autoSearch) {
            $matchingBooks = Book::where(function ($q) use ($tagName) {
                $q->where('title', 'like', "%{$tagName}%")
                  ->orWhere('author', 'like', "%{$tagName}%");
            })->pluck('id');

            foreach ($matchingBooks as $bid) {
                if (!BookTag::where('book_id', $bid)->where('tag_name', $tagName)->exists()) {
                    BookTag::create([
                        'book_id' => $bid,
                        'tag_name' => $tagName,
                    ]);
                    $addedCount++;
                }
            }

            if ($addedCount > 0) {
                return redirect()->route('admin.tags')->with('message', "标签添加成功！已为 {$addedCount} 本匹配的书籍添加了标签");
            } else {
                return redirect()->route('admin.tags')->with('message', '未找到新的匹配书籍，所有匹配的书籍已包含此标签');
            }
        } else {
            if ($bookId) {
                if (!BookTag::where('book_id', $bookId)->where('tag_name', $tagName)->exists()) {
                    BookTag::create([
                        'book_id' => $bookId,
                        'tag_name' => $tagName,
                    ]);
                    return redirect()->route('admin.tags')->with('message', '标签添加成功');
                } else {
                    return redirect()->route('admin.tags')->with('message', '该书籍已包含此标签');
                }
            } else {
                return redirect()->route('admin.add_tag')->with('error', '请启用自动搜索功能或输入图书ID');
            }
        }
    }

    /**
     * 编辑标签页面
     */
    public function showEditTag(Request $request)
    {
        $oldTagName = trim($request->get('tag', ''));
        if (!$oldTagName) {
            return redirect()->route('admin.tags')->with('error', '未指定标签名称');
        }

        return view('admin.edit_tag', [
            'action' => 'edit_tag',
            'pageTitle' => '编辑标签 - 七秒电子书后台管理',
            'old_tag_name' => $oldTagName,
            'error' => $request->get('error'),
        ]);
    }

    /**
     * 处理编辑标签
     */
    public function postEditTag(Request $request)
    {
        $oldTagName = trim($request->get('tag', ''));
        if (!$oldTagName) {
            return redirect()->route('admin.tags')->with('error', '未指定标签名称');
        }

        $request->validate([
            'new_tag_name' => 'required|string',
        ]);

        $newTagName = trim($request->input('new_tag_name'));

        if (!$newTagName || $oldTagName === $newTagName) {
            return redirect()->route('admin.edit_tag', ['tag' => $oldTagName])->with('error', '标签名称不能为空或相同');
        }

        $exists = BookTag::where('tag_name', $newTagName)->exists();

        if ($exists) {
            // 合并标签
            BookTag::where('tag_name', $oldTagName)->update(['tag_name' => $newTagName]);

            // 删除重复的标签
            DB::statement("
                DELETE t1 FROM book_tags t1
                INNER JOIN book_tags t2 
                WHERE t1.id > t2.id 
                AND t1.tag_name = t2.tag_name 
                AND t1.book_id = t2.book_id
            ");

            return redirect()->route('admin.tags')->with('message', '标签已重命名并合并');
        } else {
            BookTag::where('tag_name', $oldTagName)->update(['tag_name' => $newTagName]);
            return redirect()->route('admin.tags')->with('message', '标签重命名成功');
        }
    }

    /**
     * 删除标签
     */
    public function deleteTag(Request $request)
    {
        $tagName = trim($request->get('tag', ''));
        $confirm = $request->get('confirm');

        if ($tagName && $confirm === 'yes') {
            BookTag::where('tag_name', $tagName)->delete();
            return redirect()->route('admin.tags')->with('message', '标签删除成功');
        }

        return redirect()->route('admin.tags');
    }

    /**
     * 显示合并标签页面
     */
    public function showMergeTags()
    {
        return view('admin.merge_tags', [
            'action' => 'merge_tags',
            'pageTitle' => '合并标签 - 七秒电子书后台管理',
            'error' => request()->get('error'),
        ]);
    }

    /**
     * 处理合并标签
     */
    public function postMergeTags(Request $request)
    {
        $request->validate([
            'source_tag' => 'required|string',
            'target_tag' => 'required|string',
        ]);

        $sourceTag = trim($request->input('source_tag'));
        $targetTag = trim($request->input('target_tag'));

        if (!$sourceTag || !$targetTag || $sourceTag === $targetTag) {
            return redirect()->route('admin.merge_tags')->with('error', '请选择要合并的标签，且源标签和目标标签不能相同');
        }

        $targetExists = BookTag::where('tag_name', $targetTag)->exists();

        if (!$targetExists) {
            // 如果目标标签不存在，直接重命名源标签
            BookTag::where('tag_name', $sourceTag)->update(['tag_name' => $targetTag]);
            return redirect()->route('admin.tags')->with('message', '标签合并成功（源标签已重命名为目标标签）');
        } else {
            // 合并标签：将源标签的书籍添加到目标标签（如果不存在）
            $sourceBooks = BookTag::where('tag_name', $sourceTag)->pluck('book_id');
            foreach ($sourceBooks as $bookId) {
                if (!BookTag::where('book_id', $bookId)->where('tag_name', $targetTag)->exists()) {
                    BookTag::create([
                        'book_id' => $bookId,
                        'tag_name' => $targetTag,
                    ]);
                }
            }

            // 删除源标签
            BookTag::where('tag_name', $sourceTag)->delete();

            return redirect()->route('admin.tags')->with('message', '标签合并成功');
        }
    }

    /**
     * 查看标签书籍
     */
    public function viewTagBooks(Request $request)
    {
        $tagName = trim($request->get('tag', ''));
        if (!$tagName) {
            return redirect()->route('admin.tags')->with('error', '未指定标签名称');
        }

        $tagBooks = Book::select('books.id', 'books.title', 'books.author')
            ->join('book_tags', 'books.id', '=', 'book_tags.book_id')
            ->where('book_tags.tag_name', $tagName)
            ->orderBy('books.id')
            ->get();

        return view('admin.view_tag_books', [
            'action' => 'view_tag_books',
            'pageTitle' => '标签书籍列表 - 七秒电子书后台管理',
            'tag_name' => $tagName,
            'tag_books' => $tagBooks,
        ]);
    }

    /**
     * 管理员设置页面
     */
    public function adminSettings()
    {
        $adminUser = User::where('role', 'admin')->first();

        return view('admin.admin_settings', [
            'action' => 'admin_settings',
            'pageTitle' => '管理员设置 - 七秒电子书后台管理',
            'admin_user' => $adminUser,
            'message' => request()->get('message'),
            'error' => request()->get('error'),
        ]);
    }

    /**
     * 处理管理员设置
     */
    public function postAdminSettings(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|integer',
            'username' => 'required|string',
            'email' => 'required|email',
            'password' => 'nullable|string',
        ]);

        $adminId = (int) $request->input('admin_id');
        $username = trim($request->input('username'));
        $email = strtolower(trim($request->input('email')));
        $password = $request->input('password');

        $admin = User::findOrFail($adminId);

        // 验证用户名
        $usernameValidation = $this->userService->validateUsername($username);
        if (!$usernameValidation['valid']) {
            return redirect()->route('admin.settings')->with('error', $usernameValidation['message']);
        }

        // 检查用户名是否已被其他用户使用
        if (User::where('username', $username)->where('id', '!=', $adminId)->exists()) {
            return redirect()->route('admin.settings')->with('error', '用户名已被其他用户使用');
        }

        // 检查邮箱是否已被其他用户使用
        if (User::where('email', $email)->where('id', '!=', $adminId)->exists()) {
            return redirect()->route('admin.settings')->with('error', '该邮箱已被其他用户使用');
        }

        $admin->username = $username;
        $admin->email = $email;

        if ($password) {
            $passwordValidation = $this->userService->validatePassword($password);
            if (!$passwordValidation['valid']) {
                return redirect()->route('admin.settings')->with('error', $passwordValidation['message']);
            }
            $admin->password_hash = Hash::make($password);
        }

        $admin->save();

        // 更新当前登录用户的session
        if (Auth::id() == $adminId) {
            Auth::login($admin);
        }

        return redirect()->route('admin.settings')->with('message', '管理员信息更新成功');
    }
}

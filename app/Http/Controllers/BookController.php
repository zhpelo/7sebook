<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Book;
use App\Services\BookLikeService;
use App\Services\DownloadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    protected BookLikeService $bookLikeService;
    protected DownloadService $downloadService;

    public function __construct(
        BookLikeService $bookLikeService,
        DownloadService $downloadService
    ) {
        $this->bookLikeService = $bookLikeService;
        $this->downloadService = $downloadService;
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

        $downloadSettings = $this->getDownloadSettings();
        $role = $this->resolveDownloadRole();
        $remainingDownloads = null;

        if ($this->isRoleAllowedToDownload($role, $downloadSettings['download_permission'])) {
            $dailyLimit = $this->getDailyLimitByRole($role, $downloadSettings);
            $todayDownloadCount = $this->getTodayDownloadCountByRole($role, $request);
            $remainingDownloads = max(0, $dailyLimit - $todayDownloadCount);
        }

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
            'remaining_downloads' => $remainingDownloads,
            'can_guest_download' => $downloadSettings['download_permission'] === 'guest',
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
        $bookId = (int) $id;

        if ($bookId <= 0) {
            return redirect()->route('home')->with('error', '无效的书籍ID');
        }

        $settings = $this->getDownloadSettings();
        $role = $this->resolveDownloadRole();

        if (!$this->isRoleAllowedToDownload($role, $settings['download_permission'])) {
            if ($role === 'guest') {
                session(['intended_url' => route('book.detail', $bookId)]);
                return redirect()->route('login')->with('error', '当前下载权限仅限注册用户或VIP用户');
            }

            return redirect()->route('book.detail', $bookId)->with('error', '当前下载权限仅限VIP用户');
        }

        $subjectKey = $role === 'guest' ? 'guest:' . ($request->ip() ?: 'unknown') : 'user:' . Auth::id();
        $dailyLimit = $this->getDailyLimitByRole($role, $settings);
        $todayDownloadCount = $this->getTodayDownloadCountByRole($role, $request);

        if ($todayDownloadCount >= $dailyLimit) {
            return redirect()->route('book.detail', $bookId)->with('error', '今日下载次数已达上限，请明天再试');
        }

        // 检查短时间内是否重复请求下载（30秒内只允许一次）
        if (!$this->downloadService->canDownloadBySubjectKey($subjectKey, 30)) {
            return redirect()->route('book.detail', $bookId)->with('error', '下载请求过于频繁，请30秒后再试');
        }

        // 获取书籍信息
        $book = Book::find($bookId);

        if (!$book) {
            return redirect()->route('home')->with('error', '书籍不存在');
        }

        if ($role !== 'guest') {
            $userId = (int) Auth::id();

            if (!$this->downloadService->tryLogDownload($userId, $bookId, $request->ip(), $dailyLimit)) {
                $this->downloadService->clearDownloadLockBySubjectKey($subjectKey);
                return redirect()->route('book.detail', $bookId)->with('error', '今日下载次数已达上限，请明天再试');
            }
        }

        // 下载日志已成功记录，现在提供文件下载
        $filepath = Storage::disk('local')->path($book->file_path);

        if (!file_exists($filepath)) {
            // 清除下载锁，因为文件不存在
            $this->downloadService->clearDownloadLockBySubjectKey($subjectKey);
            return redirect()->route('book.detail', $bookId)->with('error', '文件不存在');
        }

        if ($role === 'guest') {
            $this->downloadService->incrementGuestDownloadCount($request->ip() ?: 'unknown');
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

    private function getDownloadSettings(): array
    {
        $defaults = [
            'download_permission' => 'user',
            'download_limit_guest' => '0',
            'download_limit_user' => '5',
            'download_limit_vip' => '20',
        ];

        $settings = AppSetting::getValues($defaults);

        return [
            'download_permission' => in_array($settings['download_permission'], ['guest', 'user', 'vip'], true)
                ? $settings['download_permission']
                : 'user',
            'download_limit_guest' => max(0, (int) $settings['download_limit_guest']),
            'download_limit_user' => max(0, (int) $settings['download_limit_user']),
            'download_limit_vip' => max(0, (int) $settings['download_limit_vip']),
        ];
    }

    private function resolveDownloadRole(): string
    {
        if (!Auth::check()) {
            return 'guest';
        }

        return Auth::user()?->role === 'vip' ? 'vip' : 'user';
    }

    private function isRoleAllowedToDownload(string $role, string $permission): bool
    {
        $weights = ['guest' => 1, 'user' => 2, 'vip' => 3];

        return ($weights[$role] ?? 0) >= ($weights[$permission] ?? 2);
    }

    private function getDailyLimitByRole(string $role, array $settings): int
    {
        if ($role === 'guest') {
            return $settings['download_limit_guest'];
        }

        if ($role === 'vip') {
            return $settings['download_limit_vip'];
        }

        return $settings['download_limit_user'];
    }

    private function getTodayDownloadCountByRole(string $role, Request $request): int
    {
        if ($role === 'guest') {
            return $this->downloadService->getGuestDownloadCount($request->ip() ?: 'unknown');
        }

        return $this->downloadService->getUserDownloadCount(Auth::id());
    }

    /**
     * 标签书籍列表
     */
    public function tagBooks(Request $request, $tag)
    {
        $tagName = urldecode($tag);
        
        if (empty($tagName)) {
            return redirect()->route('home')->with('error', '标签名称不能为空');
        }

        // 获取该标签下的所有书籍（去重）
        $books = Book::select('books.*')
            ->join('book_tags', 'books.id', '=', 'book_tags.book_id')
            ->where('book_tags.tag_name', $tagName)
            ->groupBy('books.id')
            ->orderByDesc('books.created_at')
            ->paginate(24);

        // 获取标签使用次数（去重book_id）
        $tagUsageCount = DB::table('book_tags')
            ->where('tag_name', $tagName)
            ->distinct('book_id')
            ->count('book_id');

        return view('tag_books', [
            'action' => 'tag_books',
            'tag_name' => $tagName,
            'books' => $books,
            'tag_usage_count' => $tagUsageCount,
            'pageTitle' => $tagName . ' - 标签 - 七秒电子书',
            'pageDescription' => '查看标签"' . htmlspecialchars($tagName) . '"下的所有电子书，共' . $tagUsageCount . '本',
            'pageKeywords' => htmlspecialchars($tagName) . ',电子书,标签,七秒电子书',
        ]);
    }
}

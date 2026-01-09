<?php

namespace App\Http\Controllers;

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

        // 检查短时间内是否重复请求下载同一本书（10秒内）
        if (!$this->downloadService->canDownload($userId, $bookId, 10)) {
            return redirect()->route('book.detail', $bookId)->with('error', '下载请求过于频繁，请稍后再试');
        }

        // 获取书籍信息
        $book = Book::find($bookId);

        if (!$book) {
            return redirect()->route('home')->with('error', '书籍不存在');
        }

        // 使用原子操作尝试记录下载日志
        if (!$this->downloadService->tryLogDownload($userId, $bookId)) {
            // 清除下载锁，因为下载失败
            $this->downloadService->clearDownloadLock($userId, $bookId);
            return redirect()->route('book.detail', $bookId)->with('error', '今日下载次数已达上限（5次），请明天再试');
        }

        // 下载日志已成功记录，现在提供文件下载
        $filepath = Storage::disk('local')->path($book->file_path);

        if (!file_exists($filepath)) {
            // 清除下载锁，因为文件不存在
            $this->downloadService->clearDownloadLock($userId, $bookId);
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

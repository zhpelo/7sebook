<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    protected const LIMIT = 20;

    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * 书籍管理列表
     */
    public function books(Request $request)
    {
        $bookSearch = trim($request->get('search', ''));
        $page = max(1, (int) $request->get('page', 1));
        $limit = self::LIMIT;
        $offset = ($page - 1) * $limit;

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = strtolower((string) $request->get('sort_direction', 'desc'));
        $fileFormat = trim($request->get('file_format', ''));
        $sizeMinMb = $request->get('size_min');
        $sizeMaxMb = $request->get('size_max');

        $allowedSortBy = ['file_size', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortBy, true)) {
            $sortBy = 'created_at';
        }

        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        $sizeMinMb = (is_numeric($sizeMinMb) && (float) $sizeMinMb >= 0) ? (float) $sizeMinMb : null;
        $sizeMaxMb = (is_numeric($sizeMaxMb) && (float) $sizeMaxMb >= 0) ? (float) $sizeMaxMb : null;

        if ($sizeMinMb !== null && $sizeMaxMb !== null && $sizeMinMb > $sizeMaxMb) {
            [$sizeMinMb, $sizeMaxMb] = [$sizeMaxMb, $sizeMinMb];
        }

        $sizeExpression = $this->getFileSizeBytesExpression();

        $query = Book::with('tags');

        if (!empty($bookSearch)) {
            $query->where(function ($q) use ($bookSearch) {
                $q->where('title', 'like', "%{$bookSearch}%")
                  ->orWhere('author', 'like', "%{$bookSearch}%");
            });
        }

        if (!empty($fileFormat)) {
            $query->where('file_format', $fileFormat);
        }

        if ($sizeMinMb !== null) {
            $query->whereRaw("{$sizeExpression} >= ?", [(int) round($sizeMinMb * 1024 * 1024)]);
        }

        if ($sizeMaxMb !== null) {
            $query->whereRaw("{$sizeExpression} <= ?", [(int) round($sizeMaxMb * 1024 * 1024)]);
        }

        if ($sortBy === 'file_size') {
            $query->orderByRaw("{$sizeExpression} {$sortDirection}");
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        // 先计算总数，再做分页
        $totalBooks = (clone $query)->count();

        $books = $query
            ->limit($limit)
            ->offset($offset)
            ->get();

        $totalPages = max(1, (int) ceil($totalBooks / $limit));
        $fileFormats = Book::query()
            ->select('file_format')
            ->whereNotNull('file_format')
            ->where('file_format', '!=', '')
            ->distinct()
            ->orderBy('file_format')
            ->pluck('file_format');

        return view('admin.books', [
            'action' => 'books',
            'pageTitle' => '书籍管理 - 七秒电子书后台管理',
            'books' => $books,
            'book_search' => $bookSearch,
            'sort_by' => $sortBy,
            'sort_direction' => $sortDirection,
            'file_format' => $fileFormat,
            'size_min' => $sizeMinMb,
            'size_max' => $sizeMaxMb,
            'file_formats' => $fileFormats,
            'pagination_query' => $request->except('page'),
            'page' => $page,
            'total_pages' => $totalPages,
            'total_books' => $totalBooks,
            'limit' => $limit,
            'message' => $request->get('message'),
            'error' => $request->get('error'),
        ]);
    }

    private function getFileSizeBytesExpression(): string
    {
        return "CASE
            WHEN file_size IS NULL OR TRIM(file_size) = '' THEN 0
            WHEN UPPER(REPLACE(file_size, ' ', '')) LIKE '%TB' THEN CAST(REPLACE(UPPER(REPLACE(file_size, ' ', '')), 'TB', '') AS DECIMAL(20,4)) * 1099511627776
            WHEN UPPER(REPLACE(file_size, ' ', '')) LIKE '%GB' THEN CAST(REPLACE(UPPER(REPLACE(file_size, ' ', '')), 'GB', '') AS DECIMAL(20,4)) * 1073741824
            WHEN UPPER(REPLACE(file_size, ' ', '')) LIKE '%MB' THEN CAST(REPLACE(UPPER(REPLACE(file_size, ' ', '')), 'MB', '') AS DECIMAL(20,4)) * 1048576
            WHEN UPPER(REPLACE(file_size, ' ', '')) LIKE '%KB' THEN CAST(REPLACE(UPPER(REPLACE(file_size, ' ', '')), 'KB', '') AS DECIMAL(20,4)) * 1024
            WHEN UPPER(REPLACE(file_size, ' ', '')) LIKE '%B' THEN CAST(REPLACE(UPPER(REPLACE(file_size, ' ', '')), 'B', '') AS DECIMAL(20,4))
            ELSE CAST(REPLACE(REPLACE(file_size, ',', ''), ' ', '') AS DECIMAL(20,4))
        END";
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
            'publisher' => 'nullable|string',
            'page_count' => 'nullable|integer',
            'file_name' => 'nullable|string',
            'tags' => 'nullable|string',
        ]);

        $book->title = trim($request->input('title'));
        $book->author = trim($request->input('author'));
        $book->description = trim($request->input('description', ''));
        $book->publisher = trim($request->input('publisher', ''));
        $book->page_count = (int) $request->input('page_count', 0);
        $book->file_name = trim($request->input('file_name', ''));
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
            $book = Book::findOrFail($id);

            if (!$this->deleteBookStoredFile($book)) {
                $failedPath = trim((string) $book->file_path);
                $failedPath = $failedPath !== '' ? $failedPath : '(空路径)';

                return redirect()->route('admin.books')->with(
                    'error',
                    '电子书文件删除失败，书籍ID：' . $book->id . '，文件路径：' . $failedPath
                );
            }

            $book->delete();
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
                $books = Book::whereIn('id', $idArray)->get();

                foreach ($books as $book) {
                    if (!$this->deleteBookStoredFile($book)) {
                        $failedPath = trim((string) $book->file_path);
                        $failedPath = $failedPath !== '' ? $failedPath : '(空路径)';

                        return redirect()->route('admin.books')->with(
                            'error',
                            '批量删除失败，书籍ID：' . $book->id . '，文件路径：' . $failedPath
                        );
                    }
                }

                Book::whereIn('id', $books->pluck('id'))->delete();
                return redirect()->route('admin.books')->with('message', '已成功删除 ' . $books->count() . ' 本电子书');
            }
        }

        return redirect()->route('admin.books');
    }

    private function deleteBookStoredFile(Book $book): bool
    {
        $path = trim((string) $book->file_path);
        if ($path === '') {
            return true;
        }

        $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');
        if ($normalizedPath === '') {
            return true;
        }

        $disk = Storage::disk('local');

        if (!$disk->exists($normalizedPath)) {
            return true;
        }

        return $disk->delete($normalizedPath);
    }
}


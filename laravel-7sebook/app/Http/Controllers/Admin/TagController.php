<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    protected const LIMIT = 20;

    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * 标签管理列表
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
        $totalPages = (int) ceil($totalTags / $limit);

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
}


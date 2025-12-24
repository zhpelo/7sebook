<?php

namespace App\Http\Controllers;

use App\Services\BookLikeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    protected BookLikeService $bookLikeService;

    public function __construct(BookLikeService $bookLikeService)
    {
        $this->bookLikeService = $bookLikeService;
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
}

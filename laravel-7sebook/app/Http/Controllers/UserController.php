<?php

namespace App\Http\Controllers;

use App\Services\BookLikeService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected BookLikeService $bookLikeService;
    protected UserService $userService;

    public function __construct(
        BookLikeService $bookLikeService,
        UserService $userService
    ) {
        $this->bookLikeService = $bookLikeService;
        $this->userService = $userService;
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

            if (\App\Models\User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
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
}

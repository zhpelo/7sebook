<?php

use App\Http\Controllers\FrontController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\Admin\AdminController as LegacyAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\BookController as AdminBookController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\DownloadController as AdminDownloadController;
use App\Http\Controllers\Admin\SearchHistoryController as AdminSearchHistoryController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use Illuminate\Support\Facades\Route;

// 图片缩略图路由（需要在其他路由之前，避免被拦截）
// 使用通配符匹配包含斜杠的路径，如 251117/a77a9d23b5cdb58b253c6ac51ae42401.webp
Route::get('/storage/covers_sm/{path}', [ImageController::class, 'thumbnail'])
    ->where('path', '.+')
    ->name('image.thumbnail');

// 前台路由
Route::get('/', [FrontController::class, 'index'])->name('home');
Route::get('/search', [FrontController::class, 'index'])->name('search');

// 书籍相关
Route::get('/book/{id}', [FrontController::class, 'detail'])->name('book.detail');
Route::get('/download/{id}', [FrontController::class, 'download'])->name('book.download')->middleware('auth');

// 用户认证
Route::get('/login', [FrontController::class, 'showLogin'])->name('login');
Route::post('/login', [FrontController::class, 'handleLogin'])->name('login.post');
Route::get('/register', [FrontController::class, 'showRegister'])->name('register');
Route::post('/register', [FrontController::class, 'handleRegister'])->name('register.post');
Route::get('/logout', [FrontController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [FrontController::class, 'showForgotPassword'])->name('forgot_password');
Route::post('/forgot-password', [FrontController::class, 'handleForgotPassword'])->name('forgot_password.post');
Route::get('/reset-password', [FrontController::class, 'showResetPassword'])->name('reset_password');
Route::post('/reset-password', [FrontController::class, 'handleResetPassword'])->name('reset_password.post');

// 用户中心（需要登录）
Route::middleware('auth')->group(function () {
    Route::get('/profile', [FrontController::class, 'profile'])->name('profile');
    Route::post('/profile', [FrontController::class, 'updateProfile'])->name('profile.update');
    Route::get('/my-likes', [FrontController::class, 'myLikes'])->name('my_likes');
    Route::get('/my-favorites', [FrontController::class, 'myFavorites'])->name('my_favorites');
});

// 点赞和收藏（AJAX）
Route::middleware('auth')->group(function () {
    Route::post('/like', [FrontController::class, 'like'])->name('book.like');
    Route::post('/unlike', [FrontController::class, 'unlike'])->name('book.unlike');
    Route::post('/favorite', [FrontController::class, 'favorite'])->name('book.favorite');
    Route::post('/unfavorite', [FrontController::class, 'unfavorite'])->name('book.unfavorite');
});

// 其他页面
Route::get('/popular-searches', [FrontController::class, 'popularSearches'])->name('popular_searches');
Route::get('/download-ranking', [FrontController::class, 'downloadRanking'])->name('download_ranking');
Route::get('/latest', [FrontController::class, 'latest'])->name('latest');

// Sitemap
Route::get('/sitemap.xml', [FrontController::class, 'generateSitemap'])->name('sitemap');

// 后台管理路由（需要管理员权限）
Route::prefix('admin')->middleware('admin')->group(function () {
    // 仪表盘
    Route::get('/', [DashboardController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('admin.dashboard');

    // 用户管理
    Route::get('/users', [AdminUserController::class, 'users'])->name('admin.users');
    Route::get('/users/add', [AdminUserController::class, 'showAddUser'])->name('admin.add_user');
    Route::post('/users/add', [AdminUserController::class, 'postAddUser'])->name('admin.add_user.post');
    Route::get('/users/{id}/edit', [AdminUserController::class, 'showEditUser'])->name('admin.edit_user');
    Route::post('/users/{id}/edit', [AdminUserController::class, 'postEditUser'])->name('admin.edit_user.post');
    Route::get('/users/{id}/delete', [AdminUserController::class, 'deleteUser'])->name('admin.delete_user');
    Route::get('/users/batch-delete', [AdminUserController::class, 'batchDeleteUsers'])->name('admin.batch_delete_users');

    // 书籍管理
    Route::get('/books', [AdminBookController::class, 'books'])->name('admin.books');
    Route::get('/books/add', [AdminBookController::class, 'showAddBook'])->name('admin.add_book');
    Route::post('/books/add', [AdminBookController::class, 'postAddBook'])->name('admin.add_book.post');
    Route::get('/books/{id}/edit', [AdminBookController::class, 'showEditBook'])->name('admin.edit_book');
    Route::post('/books/{id}/edit', [AdminBookController::class, 'postEditBook'])->name('admin.edit_book.post');
    Route::get('/books/{id}/delete', [AdminBookController::class, 'deleteBook'])->name('admin.delete_book');
    Route::get('/books/batch-delete', [AdminBookController::class, 'batchDeleteBooks'])->name('admin.batch_delete_books');

    // 下载记录管理
    Route::get('/downloads', [AdminDownloadController::class, 'downloads'])->name('admin.downloads');

    // 搜索历史管理
    Route::get('/search-history', [AdminSearchHistoryController::class, 'searchHistory'])->name('admin.search_history');

    // 标签管理
    Route::get('/tags', [AdminTagController::class, 'tags'])->name('admin.tags');
    Route::get('/tags/add', [AdminTagController::class, 'showAddTag'])->name('admin.add_tag');
    Route::post('/tags/add', [AdminTagController::class, 'postAddTag'])->name('admin.add_tag.post');
    Route::get('/tags/edit', [AdminTagController::class, 'showEditTag'])->name('admin.edit_tag');
    Route::post('/tags/edit', [AdminTagController::class, 'postEditTag'])->name('admin.edit_tag.post');
    Route::get('/tags/delete', [AdminTagController::class, 'deleteTag'])->name('admin.delete_tag');
    Route::get('/tags/merge', [AdminTagController::class, 'showMergeTags'])->name('admin.merge_tags');
    Route::post('/tags/merge', [AdminTagController::class, 'postMergeTags'])->name('admin.merge_tags.post');
    Route::get('/tags/view-books', [AdminTagController::class, 'viewTagBooks'])->name('admin.view_tag_books');

    // 管理员设置
    Route::get('/settings', [AdminSettingController::class, 'adminSettings'])->name('admin.settings');
    Route::post('/settings', [AdminSettingController::class, 'postAdminSettings'])->name('admin.settings.post');
});

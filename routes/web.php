<?php

use App\Http\Controllers\ImageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Admin\AdminController as LegacyAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\BookController as AdminBookController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\DownloadController as AdminDownloadController;
use App\Http\Controllers\Admin\SearchHistoryController as AdminSearchHistoryController;
use App\Http\Controllers\Admin\EmailLogController as AdminEmailLogController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use Illuminate\Support\Facades\Route;

// 图片缩略图路由（需要在其他路由之前，避免被拦截）
// 使用通配符匹配包含斜杠的路径，如 251117/a77a9d23b5cdb58b253c6ac51ae42401.webp
Route::get('/storage/covers_sm/{path}', [ImageController::class, 'thumbnail'])
    ->where('path', '.+')
    ->name('image.thumbnail');

   
// 前台路由（搜索需登录）
Route::middleware('auth')->group(function () {
    Route::get('/search', [SearchController::class, 'search'])->name('search');
});

Route::get('/', [SearchController::class, 'home'])->name('home');
// 书籍相关
Route::get('/book/{id}', [BookController::class, 'detail'])->name('book.detail');
Route::get('/download/{id}', [BookController::class, 'download'])->name('book.download');
Route::get('/tag/{tag}', [BookController::class, 'tagBooks'])->name('tag.books');

// 用户认证
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'handleLogin'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'handleRegister'])->name('register.post');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('forgot_password');
Route::post('/forgot-password', [AuthController::class, 'handleForgotPassword'])->name('forgot_password.post');
Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('reset_password');
Route::post('/reset-password', [AuthController::class, 'handleResetPassword'])->name('reset_password.post');

// 用户中心（需要登录）
Route::middleware('auth')->group(function () {
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::post('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::get('/my-likes', [UserController::class, 'myLikes'])->name('my_likes');
    Route::get('/my-favorites', [UserController::class, 'myFavorites'])->name('my_favorites');
});

// 点赞和收藏（AJAX）
Route::middleware('auth')->group(function () {
    Route::post('/like', [LikeController::class, 'like'])->name('book.like');
    Route::post('/unlike', [LikeController::class, 'unlike'])->name('book.unlike');
    Route::post('/favorite', [LikeController::class, 'favorite'])->name('book.favorite');
    Route::post('/unfavorite', [LikeController::class, 'unfavorite'])->name('book.unfavorite');
});

// 其他页面
Route::get('/popular-searches', [SearchController::class, 'popularSearches'])->name('popular_searches');
Route::get('/download-ranking', [RankingController::class, 'downloadRanking'])->name('download_ranking');
Route::get('/latest', [RankingController::class, 'latest'])->name('latest');

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'generateSitemap'])->name('sitemap');

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

    // 邮件发送记录管理
    Route::get('/email-logs', [AdminEmailLogController::class, 'emailLogs'])->name('admin.email_logs');
    Route::get('/email-logs/{id}', [AdminEmailLogController::class, 'viewEmailLog'])->name('admin.view_email_log');

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
    Route::get('/site-settings', [AdminSettingController::class, 'siteSettings'])->name('admin.site_settings');
    Route::post('/site-settings', [AdminSettingController::class, 'postSiteSettings'])->name('admin.site_settings.post');
    Route::get('/download-settings', [AdminSettingController::class, 'downloadSettings'])->name('admin.download_settings');
    Route::post('/download-settings', [AdminSettingController::class, 'postDownloadSettings'])->name('admin.download_settings.post');
});

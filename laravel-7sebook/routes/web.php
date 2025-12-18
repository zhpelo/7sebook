<?php

use App\Http\Controllers\FrontController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Route;

// 图片缩略图路由（需要在其他路由之前，避免被拦截）
// 使用通配符匹配包含斜杠的路径，如 251117/a77a9d23b5cdb58b253c6ac51ae42401.webp
Route::get('/covers_sm/{path}', [ImageController::class, 'thumbnail'])
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
    Route::get('/', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // 用户管理
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/users/add', [AdminController::class, 'showAddUser'])->name('admin.add_user');
    Route::post('/users/add', [AdminController::class, 'postAddUser'])->name('admin.add_user.post');
    Route::get('/users/{id}/edit', [AdminController::class, 'showEditUser'])->name('admin.edit_user');
    Route::post('/users/{id}/edit', [AdminController::class, 'postEditUser'])->name('admin.edit_user.post');
    Route::get('/users/{id}/delete', [AdminController::class, 'deleteUser'])->name('admin.delete_user');
    Route::get('/users/batch-delete', [AdminController::class, 'batchDeleteUsers'])->name('admin.batch_delete_users');

    // 书籍管理
    Route::get('/books', [AdminController::class, 'books'])->name('admin.books');
    Route::get('/books/add', [AdminController::class, 'showAddBook'])->name('admin.add_book');
    Route::post('/books/add', [AdminController::class, 'postAddBook'])->name('admin.add_book.post');
    Route::get('/books/{id}/edit', [AdminController::class, 'showEditBook'])->name('admin.edit_book');
    Route::post('/books/{id}/edit', [AdminController::class, 'postEditBook'])->name('admin.edit_book.post');
    Route::get('/books/{id}/delete', [AdminController::class, 'deleteBook'])->name('admin.delete_book');
    Route::get('/books/batch-delete', [AdminController::class, 'batchDeleteBooks'])->name('admin.batch_delete_books');

    // 下载记录管理
    Route::get('/downloads', [AdminController::class, 'downloads'])->name('admin.downloads');

    // 搜索历史管理
    Route::get('/search-history', [AdminController::class, 'searchHistory'])->name('admin.search_history');

    // 标签管理
    Route::get('/tags', [AdminController::class, 'tags'])->name('admin.tags');
    Route::get('/tags/add', [AdminController::class, 'showAddTag'])->name('admin.add_tag');
    Route::post('/tags/add', [AdminController::class, 'postAddTag'])->name('admin.add_tag.post');
    Route::get('/tags/edit', [AdminController::class, 'showEditTag'])->name('admin.edit_tag');
    Route::post('/tags/edit', [AdminController::class, 'postEditTag'])->name('admin.edit_tag.post');
    Route::get('/tags/delete', [AdminController::class, 'deleteTag'])->name('admin.delete_tag');
    Route::get('/tags/merge', [AdminController::class, 'showMergeTags'])->name('admin.merge_tags');
    Route::post('/tags/merge', [AdminController::class, 'postMergeTags'])->name('admin.merge_tags.post');
    Route::get('/tags/view-books', [AdminController::class, 'viewTagBooks'])->name('admin.view_tag_books');

    // 管理员设置
    Route::get('/settings', [AdminController::class, 'adminSettings'])->name('admin.settings');
    Route::post('/settings', [AdminController::class, 'postAdminSettings'])->name('admin.settings.post');
});

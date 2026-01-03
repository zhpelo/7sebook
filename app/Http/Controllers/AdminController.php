<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Book;
use App\Models\DownloadLog;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->middleware('admin');
        $this->userService = $userService;
    }

    /**
     * 保留 AdminController 作为兼容入口，目前仅包含与管理员自身相关的逻辑。
     * 仪表盘、用户管理、书籍管理、标签管理、下载记录、搜索记录等
     * 已拆分到 App\Http\Controllers\Admin 下的多个 Controller 中。
     */
}

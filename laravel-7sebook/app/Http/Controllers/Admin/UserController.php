<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected UserService $userService;
    protected const LIMIT = 20;

    public function __construct(UserService $userService)
    {
        $this->middleware('admin');
        $this->userService = $userService;
    }

    /**
     * 用户管理列表
     */
    public function users(Request $request)
    {
        $userSearch = trim($request->get('search', ''));
        $page = max(1, (int) $request->get('page', 1));
        $limit = self::LIMIT;
        $offset = ($page - 1) * $limit;

        $query = User::query();

        if (!empty($userSearch)) {
            $query->where(function ($q) use ($userSearch) {
                $q->where('username', 'like', "%{$userSearch}%")
                  ->orWhere('email', 'like', "%{$userSearch}%");
            });
        }

        // 先计算总数，再做分页，避免 count 只统计当前页
        $totalUsers = (clone $query)->count();

        $users = $query->orderByDesc('created_at')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $totalPages = (int) ceil($totalUsers / $limit);

        return view('admin.users', [
            'action' => 'users',
            'pageTitle' => '用户管理 - 七秒电子书后台管理',
            'users' => $users,
            'user_search' => $userSearch,
            'page' => $page,
            'total_pages' => $totalPages,
            'total_users' => $totalUsers,
            'limit' => $limit,
            'message' => $request->get('message'),
            'error' => $request->get('error'),
        ]);
    }

    /**
     * 添加用户页面
     */
    public function showAddUser()
    {
        return view('admin.add_user', [
            'action' => 'add_user',
            'pageTitle' => '添加用户 - 七秒电子书后台管理',
            'error' => request()->get('error'),
        ]);
    }

    /**
     * 处理添加用户
     */
    public function postAddUser(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'email' => 'required|email',
            'role' => 'required|in:user,admin',
        ]);

        $username = trim($request->input('username'));
        $password = $request->input('password');
        $email = strtolower(trim($request->input('email')));
        $role = $request->input('role', 'user');

        // 验证用户名
        $usernameValidation = $this->userService->validateUsername($username);
        if (!$usernameValidation['valid']) {
            return redirect()->route('admin.add_user')->with('error', $usernameValidation['message']);
        }

        // 验证密码
        $passwordValidation = $this->userService->validatePassword($password);
        if (!$passwordValidation['valid']) {
            return redirect()->route('admin.add_user')->with('error', $passwordValidation['message']);
        }

        // 检查用户名是否已存在
        if (User::where('username', $username)->exists()) {
            return redirect()->route('admin.add_user')->with('error', '用户名已存在');
        }

        // 检查邮箱是否已存在
        if (User::where('email', $email)->exists()) {
            return redirect()->route('admin.add_user')->with('error', '该邮箱已被注册');
        }

        User::create([
            'username' => $username,
            'password_hash' => Hash::make($password),
            'email' => $email,
            'role' => $role,
        ]);

        return redirect()->route('admin.users')->with('message', '用户添加成功');
    }

    /**
     * 编辑用户页面
     */
    public function showEditUser($id)
    {
        $user = User::findOrFail($id);

        return view('admin.edit_user', [
            'action' => 'edit_user',
            'pageTitle' => '编辑用户 - 七秒电子书后台管理',
            'edit_user' => $user,
            'error' => request()->get('error'),
        ]);
    }

    /**
     * 处理编辑用户
     */
    public function postEditUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'username' => 'required|string',
            'email' => 'required|email',
            'role' => 'required|in:user,admin',
            'password' => 'nullable|string',
        ]);

        $username = trim($request->input('username'));
        $email = strtolower(trim($request->input('email')));
        $role = $request->input('role', 'user');
        $newPassword = $request->input('password');

        // 验证用户名
        $usernameValidation = $this->userService->validateUsername($username);
        if (!$usernameValidation['valid']) {
            return redirect()->route('admin.edit_user', $id)->with('error', $usernameValidation['message']);
        }

        // 检查用户名是否已被其他用户使用
        if (User::where('username', $username)->where('id', '!=', $id)->exists()) {
            return redirect()->route('admin.edit_user', $id)->with('error', '用户名已被其他用户使用');
        }

        // 检查邮箱是否已被其他用户使用
        if (User::where('email', $email)->where('id', '!=', $id)->exists()) {
            return redirect()->route('admin.edit_user', $id)->with('error', '该邮箱已被其他用户使用');
        }

        $user->username = $username;
        $user->email = $email;
        $user->role = $role;

        if ($newPassword) {
            $passwordValidation = $this->userService->validatePassword($newPassword);
            if (!$passwordValidation['valid']) {
                return redirect()->route('admin.edit_user', $id)->with('error', $passwordValidation['message']);
            }
            $user->password_hash = Hash::make($newPassword);
        }

        $user->save();

        return redirect()->route('admin.users')->with('message', '用户信息更新成功');
    }

    /**
     * 删除用户
     */
    public function deleteUser(Request $request, $id)
    {
        $confirm = $request->get('confirm');

        if ($confirm === 'yes') {
            User::findOrFail($id)->delete();
            return redirect()->route('admin.users')->with('message', '用户删除成功');
        }

        $user = User::findOrFail($id);

        return view('admin.delete_user', [
            'action' => 'delete_user',
            'pageTitle' => '删除用户 - 七秒电子书后台管理',
            'delete_user' => $user,
        ]);
    }

    /**
     * 批量删除用户
     */
    public function batchDeleteUsers(Request $request)
    {
        $ids = $request->get('ids');
        $confirm = $request->get('confirm');

        if ($ids && $confirm === 'yes') {
            $idArray = array_filter(array_map('intval', explode(',', $ids)));
            if (!empty($idArray)) {
                User::whereIn('id', $idArray)->delete();
                return redirect()->route('admin.users')->with('message', '已成功删除 ' . count($idArray) . ' 个用户');
            }
        }

        return redirect()->route('admin.users');
    }
}


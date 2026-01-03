<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->middleware('admin');
        $this->userService = $userService;
    }

    /**
     * 管理员设置页面
     */
    public function adminSettings()
    {
        $adminUser = User::where('role', 'admin')->first();

        return view('admin.admin_settings', [
            'action' => 'admin_settings',
            'pageTitle' => '管理员设置 - 七秒电子书后台管理',
            'admin_user' => $adminUser,
            'message' => request()->get('message'),
            'error' => request()->get('error'),
        ]);
    }

    /**
     * 处理管理员设置
     */
    public function postAdminSettings(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|integer',
            'username' => 'required|string',
            'email' => 'required|email',
            'password' => 'nullable|string',
        ]);

        $adminId = (int) $request->input('admin_id');
        $username = trim($request->input('username'));
        $email = strtolower(trim($request->input('email')));
        $password = $request->input('password');

        $admin = User::findOrFail($adminId);

        // 验证用户名
        $usernameValidation = $this->userService->validateUsername($username);
        if (!$usernameValidation['valid']) {
            return redirect()->route('admin.settings')->with('error', $usernameValidation['message']);
        }

        // 检查用户名是否已被其他用户使用
        if (User::where('username', $username)->where('id', '!=', $adminId)->exists()) {
            return redirect()->route('admin.settings')->with('error', '用户名已被其他用户使用');
        }

        // 检查邮箱是否已被其他用户使用
        if (User::where('email', $email)->where('id', '!=', $adminId)->exists()) {
            return redirect()->route('admin.settings')->with('error', '该邮箱已被其他用户使用');
        }

        $admin->username = $username;
        $admin->email = $email;

        if ($password) {
            $passwordValidation = $this->userService->validatePassword($password);
            if (!$passwordValidation['valid']) {
                return redirect()->route('admin.settings')->with('error', $passwordValidation['message']);
            }
            $admin->password_hash = Hash::make($password);
        }

        $admin->save();

        // 更新当前登录用户的session
        if (Auth::id() == $adminId) {
            Auth::login($admin);
        }

        return redirect()->route('admin.settings')->with('message', '管理员信息更新成功');
    }
}


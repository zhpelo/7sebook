<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    private const SITE_SETTING_DEFAULTS = [
        'site_name' => '七秒电子书',
        'site_description' => '专注优质电子书资源分享',
        'contact_email' => '',
    ];

    private const DOWNLOAD_SETTING_DEFAULTS = [
        'download_permission' => 'user',
        'download_limit_guest' => '0',
        'download_limit_user' => '5',
        'download_limit_vip' => '20',
    ];

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

    /**
     * 网站设置页面
     */
    public function siteSettings()
    {
        return view('admin.site_settings', [
            'action' => 'site_settings',
            'pageTitle' => '网站设置 - 七秒电子书后台管理',
            'settings' => AppSetting::getValues(self::SITE_SETTING_DEFAULTS),
            'message' => request()->get('message'),
            'error' => request()->get('error'),
        ]);
    }

    /**
     * 处理网站设置
     */
    public function postSiteSettings(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:100',
            'site_description' => 'nullable|string|max:1000',
            'contact_email' => 'nullable|email|max:255',
        ]);

        AppSetting::setValues([
            'site_name' => trim($validated['site_name']),
            'site_description' => trim($validated['site_description'] ?? ''),
            'contact_email' => strtolower(trim($validated['contact_email'] ?? '')),
        ]);

        return redirect()->route('admin.site_settings')->with('message', '网站设置更新成功');
    }

    /**
     * 下载设置页面
     */
    public function downloadSettings()
    {
        return view('admin.download_settings', [
            'action' => 'download_settings',
            'pageTitle' => '下载设置 - 七秒电子书后台管理',
            'settings' => AppSetting::getValues(self::DOWNLOAD_SETTING_DEFAULTS),
            'message' => request()->get('message'),
            'error' => request()->get('error'),
        ]);
    }

    /**
     * 处理下载设置
     */
    public function postDownloadSettings(Request $request)
    {
        $validated = $request->validate([
            'download_permission' => 'required|in:guest,user,vip',
            'download_limit_guest' => 'required|integer|min:0|max:9999',
            'download_limit_user' => 'required|integer|min:0|max:9999',
            'download_limit_vip' => 'required|integer|min:0|max:9999',
        ]);

        AppSetting::setValues([
            'download_permission' => $validated['download_permission'],
            'download_limit_guest' => (string) $validated['download_limit_guest'],
            'download_limit_user' => (string) $validated['download_limit_user'],
            'download_limit_vip' => (string) $validated['download_limit_vip'],
        ]);

        return redirect()->route('admin.download_settings')->with('message', '下载设置更新成功');
    }
}


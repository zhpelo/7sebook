<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmailLog;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * 显示登录页面
     */
    public function showLogin(Request $request)
    {
        // 如果URL中有intended参数，保存到session
        $intended = $request->get('intended');
        if ($intended) {
            session(['intended_url' => $intended]);
        }
        
        return view('login', [
            'action' => 'login',
        ]);
    }

    /**
     * 处理登录
     */
    public function handleLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = trim($request->input('username'));
        $password = $request->input('password');

        $user = User::where('username', $username)->first();

        if ($user && Hash::check($password, $user->password_hash)) {
            Auth::login($user);
            
            // 检查是否有保存的intended URL，如果有则重定向到那里
            $intendedUrl = session('intended_url');
            if ($intendedUrl) {
                session()->forget('intended_url');
                return redirect($intendedUrl);
            }
            
            return redirect()->route('home');
        }

        return redirect()->route('login')->with('error', '用户名或密码错误');
    }

    /**
     * 处理登出
     */
    public function logout()
    {
        Auth::logout();
        return redirect()->route('home');
    }

    /**
     * 显示注册页面
     */
    public function showRegister(Request $request)
    {
        // 如果URL中有intended参数，保存到session
        $intended = $request->get('intended');
        if ($intended) {
            session(['intended_url' => $intended]);
        }
        
        return view('register', [
            'action' => 'register',
        ]);
    }

    /**
     * 处理注册
     */
    public function handleRegister(Request $request)
    {
        $request->validate([
            'reg_username' => 'required|string',
            'reg_password' => 'required|string',
            'reg_email' => 'required|email',
        ]);

        $username = trim($request->input('reg_username'));
        $password = $request->input('reg_password');
        $email = strtolower(trim($request->input('reg_email')));

        // 验证用户名
        $usernameValidation = $this->userService->validateUsername($username);
        if (!$usernameValidation['valid']) {
            return redirect()->route('register')->with('error', $usernameValidation['message']);
        }

        // 验证密码
        $passwordValidation = $this->userService->validatePassword($password);
        if (!$passwordValidation['valid']) {
            return redirect()->route('register')->with('error', $passwordValidation['message']);
        }

        // 检查用户名是否已存在
        if (User::where('username', $username)->exists()) {
            return redirect()->route('register')->with('error', '用户名已存在');
        }

        // 检查邮箱是否已存在
        if (User::where('email', $email)->exists()) {
            return redirect()->route('register')->with('error', '该邮箱已被注册');
        }

        // 创建新用户
        $user = User::create([
            'username' => $username,
            'password_hash' => Hash::make($password),
            'email' => $email,
            'role' => 'user',
        ]);

        Auth::login($user);

        // 检查是否有保存的intended URL，如果有则重定向到那里
        $intendedUrl = session('intended_url');
        if ($intendedUrl) {
            session()->forget('intended_url');
            return redirect($intendedUrl);
        }

        return redirect()->route('home');
    }

    /**
     * 显示忘记密码页面
     */
    public function showForgotPassword()
    {
        return view('forgot_password', [
            'action' => 'forgot_password',
        ]);
    }

    /**
     * 处理忘记密码
     */
    public function handleForgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($request->input('email')));

        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('forgot_password')->with('error', '该邮箱未注册');
        }

        // 生成重置令牌
        $token = bin2hex(random_bytes(32));
        $expiresAt = now()->addHours(24); // 24小时后过期

        // 删除旧的令牌
        DB::table('password_resets')->where('email', $email)->delete();

        // 插入新令牌
        DB::table('password_resets')->insert([
            'email' => $email,
            'token' => $token,
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 生成重置链接
        $resetUrl = route('reset_password', ['token' => $token]);

        // 准备邮件内容
        $subject = '七秒电子书 - 密码重置';
        $mailContent = view('emails.password_reset', [
            'resetUrl' => $resetUrl,
            'username' => $user->username,
        ])->render();

        // 记录邮件发送（pending状态）
        $emailLog = EmailLog::logEmail(
            $email,
            $subject,
            $mailContent,
            EmailLog::TYPE_PASSWORD_RESET,
            EmailLog::STATUS_PENDING
        );

        try {
            // 使用 PHP mail() 函数发送邮件
            $fromEmail = config('mail.from.address', 'noreply@7sebook.com');
            $fromName = config('mail.from.name', '七秒电子书');
            
            // 设置邮件头
            $headers = [];
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
            $headers[] = "From: " . mb_encode_mimeheader($fromName, 'UTF-8') . " <{$fromEmail}>";
            $headers[] = "Reply-To: {$fromEmail}";
            $headers[] = "X-Mailer: PHP/" . phpversion();
            
            $headersString = implode("\r\n", $headers);
            
            // 发送邮件
            $mailSent = mail($email, mb_encode_mimeheader($subject, 'UTF-8'), $mailContent, $headersString);
            
            if ($mailSent) {
                // 更新邮件记录状态为已发送
                $emailLog->updateStatus(EmailLog::STATUS_SENT);
                
                return redirect()->route('forgot_password')->with('message', '密码重置链接已发送到您的邮箱，请查收。如未收到，请检查垃圾邮件文件夹。');
            } else {
                // 更新邮件记录状态为失败
                $errorMessage = 'PHP mail() 函数返回 false，邮件可能未成功发送';
                $emailLog->updateStatus(EmailLog::STATUS_FAILED, $errorMessage);
                
                // 记录错误日志
                Log::error('密码重置邮件发送失败', [
                    'email' => $email,
                    'error' => $errorMessage,
                ]);
                
                return redirect()->route('forgot_password')->with('error', '邮件发送失败，请稍后再试或联系管理员。');
            }
        } catch (\Exception $e) {
            // 更新邮件记录状态为失败
            $emailLog->updateStatus(EmailLog::STATUS_FAILED, $e->getMessage());
            
            // 记录错误日志
            Log::error('密码重置邮件发送失败', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->route('forgot_password')->with('error', '邮件发送失败，请稍后再试或联系管理员。');
        }
    }

    /**
     * 显示重置密码页面
     */
    public function showResetPassword(Request $request)
    {
        $token = $request->get('token', '');
        $resetToken = null;
        $resetEmail = null;
        $error = null;

        if ($token) {
            $resetRequest = DB::table('password_resets')
                ->where('token', $token)
                ->first();

            if ($resetRequest) {
                if (strtotime($resetRequest->expires_at) > time()) {
                    $resetToken = $token;
                    $resetEmail = $resetRequest->email;
                } else {
                    $error = '密码重置链接已过期，请重新申请。';
                    DB::table('password_resets')->where('token', $token)->delete();
                }
            } else {
                $error = '无效的密码重置链接。';
            }
        } else {
            $error = '缺少必要的参数。';
        }

        return view('reset_password', [
            'action' => 'reset_password',
            'reset_token' => $resetToken,
            'reset_email' => $resetEmail,
            'reset_password_error' => $error,
            'reset_password_success' => $request->get('success'),
        ]);
    }

    /**
     * 处理重置密码
     */
    public function handleResetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|string|same:password',
        ]);

        $token = $request->input('token');
        $email = strtolower(trim($request->input('email')));
        $password = $request->input('password');

        $resetRequest = DB::table('password_resets')
            ->where('token', $token)
            ->where('email', $email)
            ->first();

        if (!$resetRequest) {
            return redirect()->route('reset_password', ['token' => $token])->with('error', '无效的密码重置链接');
        }

        if (strtotime($resetRequest->expires_at) <= time()) {
            DB::table('password_resets')->where('token', $token)->delete();
            return redirect()->route('reset_password', ['token' => $token])->with('error', '密码重置链接已过期，请重新申请');
        }

        // 验证密码
        $passwordValidation = $this->userService->validatePassword($password);
        if (!$passwordValidation['valid']) {
            return redirect()->route('reset_password', ['token' => $token])->with('error', $passwordValidation['message']);
        }

        // 更新密码
        $user = User::where('email', $email)->first();
        if ($user) {
            $user->password_hash = Hash::make($password);
            $user->save();

            // 删除重置令牌
            DB::table('password_resets')->where('token', $token)->delete();

            return redirect()->route('login')->with('message', '密码重置成功，请登录');
        }

        return redirect()->route('reset_password', ['token' => $token])->with('error', '用户不存在');
    }
}

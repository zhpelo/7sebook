<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService
{
    /**
     * 更新用户密码
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        try {
            $user = User::findOrFail($userId);
            $user->password_hash = Hash::make($newPassword);
            return $user->save();
        } catch (\Exception $e) {
            Log::error('更新用户密码失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 更新用户邮箱
     */
    public function updateEmail(int $userId, string $newEmail): bool
    {
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            $user = User::findOrFail($userId);
            $user->email = $newEmail;
            return $user->save();
        } catch (\Exception $e) {
            Log::error('更新用户邮箱失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 验证用户名格式
     */
    public function validateUsername(string $username): array
    {
        $length = mb_strlen($username, 'UTF-8');

        if ($length < 4 || $length > 20) {
            return ['valid' => false, 'message' => '用户名长度必须在4-20个字符之间'];
        }

        // 检查是否以字母开头
        if (!preg_match('/^[a-zA-Z]/', $username)) {
            return ['valid' => false, 'message' => '用户名必须以字母开头'];
        }

        // 检查是否只包含字母、数字、下划线、连字符
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            return ['valid' => false, 'message' => '用户名只能包含字母、数字、下划线和连字符'];
        }

        // 检查是否包含连续特殊字符
        if (preg_match('/[_-]{2,}/', $username)) {
            return ['valid' => false, 'message' => '用户名不能包含连续的特殊字符（下划线或连字符）'];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * 验证密码格式
     */
    public function validatePassword(string $password): array
    {
        $length = strlen($password);

        if ($length < 6) {
            return ['valid' => false, 'message' => '密码至少需要6个字符'];
        }

        if ($length > 32) {
            return ['valid' => false, 'message' => '密码不能超过32个字符'];
        }

        return ['valid' => true, 'message' => ''];
    }
}

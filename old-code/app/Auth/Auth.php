<?php

declare(strict_types=1);

namespace App\Auth;

use App\Database\Connection;
use PDO;

/**
 * 用户认证类
 * 
 * 处理用户登录、登出、会话管理等认证相关操作
 */
class Auth
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Connection::getInstance();
    }

    /**
     * 检查用户是否已登录
     * 
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * 获取当前登录用户的ID
     * 
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    /**
     * 获取当前登录用户的角色
     * 
     * @return string
     */
    public function getUserRole(): string
    {
        return $_SESSION['user_role'] ?? 'guest';
    }

    /**
     * 检查是否为管理员
     * 
     * @return bool
     */
    public function isAdmin(): bool
    {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    /**
     * 检查是否已登录且为管理员
     * 
     * @return bool
     */
    public function isAdminLoggedIn(): bool
    {
        return $this->isLoggedIn() && $this->isAdmin();
    }

    /**
     * 设置用户会话
     * 
     * @param array<string, mixed> $user 用户信息数组
     * @return void
     */
    public function setUserSession(array $user): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'] ?? 'user';
        $_SESSION['admin_logged_in'] = ($user['role'] ?? 'user') === 'admin';
    }

    /**
     * 清除用户会话
     * 
     * @return void
     */
    public function clearUserSession(): void
    {
        unset(
            $_SESSION['user_id'],
            $_SESSION['username'],
            $_SESSION['user_role'],
            $_SESSION['admin_logged_in']
        );
    }

    /**
     * 验证用户登录
     * 
     * @param string $username 用户名
     * @param string $password 密码
     * @return array<string, mixed>|false 验证成功返回用户信息，失败返回false
     */
    public function verifyLogin(string $username, string $password)
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT id, username, password_hash, email, role 
                 FROM users 
                 WHERE username = :username 
                 LIMIT 1'
            );
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                unset($user['password_hash']);
                return $user;
            }

            return false;
        } catch (\PDOException $e) {
            error_log('用户登录验证失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取用户信息
     * 
     * @param int $userId 用户ID
     * @return array<string, mixed>|false 用户信息或false
     */
    public function getUserInfo(int $userId)
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT id, username, email, role, created_at, updated_at 
                 FROM users 
                 WHERE id = :id 
                 LIMIT 1'
            );
            $stmt->execute([':id' => $userId]);
            return $stmt->fetch() ?: false;
        } catch (\PDOException $e) {
            error_log('获取用户信息失败: ' . $e->getMessage());
            return false;
        }
    }
}

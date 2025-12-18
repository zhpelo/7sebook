<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\Auth;

/**
 * 管理员权限中间件
 * 
 * 用于检查用户是否有管理员权限
 */
class AdminMiddleware
{
    /**
     * 检查是否为管理员
     * 
     * @return bool
     */
    public static function check(): bool
    {
        // 检查session中的管理员标识
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['admin_logged_in']) && 
               $_SESSION['admin_logged_in'] === true;
    }

    /**
     * 要求管理员权限，未登录或非管理员则重定向
     * 
     * @param string $redirectUrl 重定向URL
     * @return bool 是否有权限
     */
    public static function require(string $redirectUrl = 'index.php'): bool
    {
        if (!self::check()) {
            header('Location: ' . $redirectUrl);
            exit;
        }
        return true;
    }
}

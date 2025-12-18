<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\Auth;

/**
 * 认证中间件
 * 
 * 用于检查用户是否已登录
 */
class AuthMiddleware
{
    /**
     * 检查用户是否已登录
     * 
     * @return bool
     */
    public static function check(): bool
    {
        $auth = new Auth();
        return $auth->isLoggedIn();
    }

    /**
     * 要求用户登录，未登录则重定向
     * 
     * @param string $redirectUrl 重定向URL
     * @return bool 是否已登录
     */
    public static function require(string $redirectUrl = '?action=login'): bool
    {
        if (!self::check()) {
            header('Location: ' . $redirectUrl);
            exit;
        }
        return true;
    }
}

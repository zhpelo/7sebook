<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Config\Config;

/**
 * 应用启动类
 * 
 * 负责应用的初始化工作，包括配置加载、错误处理、时区设置等
 */
class Bootstrap
{
    /**
     * 初始化应用
     */
    public static function init(): void
    {
        // 加载配置
        Config::load();

        // 设置错误报告
        self::setupErrorReporting();

        // 设置时区
        self::setupTimezone();

        // 启动会话
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * 设置错误报告
     */
    private static function setupErrorReporting(): void
    {
        if (Config::isDebug()) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }
    }

    /**
     * 设置时区
     */
    private static function setupTimezone(): void
    {
        date_default_timezone_set(Config::getTimezone());
    }

    /**
     * 加载所有必要的类文件
     */
    public static function loadClasses(): void
    {
        $classes = [
            // Config (必须先加载，因为其他类依赖它)
            __DIR__ . '/../Config/Config.php',
            // Database
            __DIR__ . '/../Database/Connection.php',
            // Auth
            __DIR__ . '/../Auth/Auth.php',
            // Helpers
            __DIR__ . '/../Helper/StringHelper.php',
            __DIR__ . '/../Helper/NetworkHelper.php',
            __DIR__ . '/../Helper/UrlHelper.php',
            __DIR__ . '/../Helper/TimeHelper.php',
            __DIR__ . '/../Helper/ViewHelper.php',
            __DIR__ . '/../Helper/SeoHelper.php',
            // Services
            __DIR__ . '/../Service/BookService.php',
            __DIR__ . '/../Service/SearchService.php',
            __DIR__ . '/../Service/SearchBuilderService.php',
            __DIR__ . '/../Service/BookLikeService.php',
            __DIR__ . '/../Service/DownloadService.php',
            __DIR__ . '/../Service/UserService.php',
        ];

        foreach ($classes as $class) {
            if (file_exists($class)) {
                require_once $class;
            }
        }
    }

    /**
     * 验证数据库连接
     */
    public static function validateDatabase(): void
    {
        try {
            \App\Database\Connection::getInstance();
        } catch (\PDOException $e) {
            if (Config::isDebug()) {
                die('数据库连接失败: ' . $e->getMessage());
            } else {
                die('数据库连接失败，请稍后再试');
            }
        }
    }
}

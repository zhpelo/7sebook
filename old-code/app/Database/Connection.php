<?php

declare(strict_types=1);

namespace App\Database;

use App\Config\Config;
use PDO;
use PDOException;

/**
 * 数据库连接管理类
 * 
 * 使用单例模式管理数据库连接
 */
class Connection
{
    private static ?PDO $instance = null;

    /**
     * 私有构造函数，防止外部实例化
     */
    private function __construct()
    {
    }

    /**
     * 获取数据库连接实例（单例模式）
     * 
     * @return PDO 数据库连接对象
     * @throws PDOException 数据库连接失败时抛出异常
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    /**
     * 创建数据库连接
     * 
     * @return PDO 数据库连接对象
     * @throws PDOException 连接失败时抛出异常
     */
    private static function createConnection(): PDO
    {
        // 确保 Config 类已加载
        if (!class_exists('App\\Config\\Config')) {
            $configFile = __DIR__ . '/../Config/Config.php';
            if (file_exists($configFile)) {
                require_once $configFile;
            }
        }

        // 确保配置已加载
        if (!Config::isDebug() && !Config::get('database.host')) {
            Config::load();
        }

        $dbConfig = Config::getDatabaseConfig();

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $dbConfig['host'] ?? 'localhost',
            $dbConfig['name'] ?? '',
            $dbConfig['charset'] ?? 'utf8mb4'
        );

        try {
            $pdo = new PDO(
                $dsn,
                $dbConfig['user'] ?? 'root',
                $dbConfig['password'] ?? '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            return $pdo;
        } catch (PDOException $e) {
            error_log('数据库连接失败: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 重置连接实例（主要用于测试）
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}

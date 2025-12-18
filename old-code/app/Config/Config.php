<?php

declare(strict_types=1);

namespace App\Config;

/**
 * 应用配置类
 * 
 * 管理应用的所有配置信息，从 .env 文件加载
 */
class Config
{
    private static array $config = [];
    private static bool $loaded = false;

    /**
     * 初始化配置（从 .env 文件加载）
     */
    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = __DIR__ . '/../../.env';

        if (!file_exists($envFile)) {
            throw new \RuntimeException('配置文件 .env 不存在，请复制 .env.example 并重命名为 .env');
        }

        // 加载 .env 文件
        self::loadEnvFile($envFile);

        // 从环境变量构建配置数组
        self::$config = [
            'timezone' => self::getEnv('TIMEZONE', 'Asia/Shanghai'),
            'debug' => self::parseBool(self::getEnv('DEBUG', 'false')),
            'database' => [
                'host' => self::getEnv('DB_HOST', 'localhost'),
                'name' => self::getEnv('DB_NAME', 'local'),
                'user' => self::getEnv('DB_USER', 'root'),
                'password' => self::getEnv('DB_PASSWORD', 'root'),
                'charset' => self::getEnv('DB_CHARSET', 'utf8mb4'),
            ],
            'supported_exts' => self::getDefaultSupportedExts(),
        ];

        self::$loaded = true;
    }

    /**
     * 加载 .env 文件
     */
    private static function loadEnvFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // 跳过注释
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // 解析 KEY=VALUE 格式
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // 移除引号
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }

                // 设置环境变量（如果尚未设置）
                if (!isset($_ENV[$key]) && !getenv($key)) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
    }

    /**
     * 获取环境变量
     */
    private static function getEnv(string $key, $default = null)
    {
        // 优先从 $_ENV 获取
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        // 从 getenv() 获取
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        return $default;
    }

    /**
     * 解析布尔值
     */
    private static function parseBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
        }
        return (bool)$value;
    }

    /**
     * 获取配置值
     * 
     * @param string $key 配置键（支持点号分隔，如 'database.host'）
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }

        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * 设置配置值
     * 
     * @param string $key 配置键
     * @param mixed $value 配置值
     */
    public static function set(string $key, $value): void
    {
        if (!self::$loaded) {
            self::load();
        }

        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * 获取时区
     */
    public static function getTimezone(): string
    {
        return self::get('timezone', 'Asia/Shanghai');
    }

    /**
     * 是否调试模式
     */
    public static function isDebug(): bool
    {
        return self::get('debug', false);
    }

    /**
     * 获取数据库配置
     */
    public static function getDatabaseConfig(): array
    {
        return self::get('database', []);
    }

    /**
     * 获取支持的文件扩展名列表
     */
    public static function getSupportedExts(): array
    {
        return self::get('supported_exts', self::getDefaultSupportedExts());
    }

    /**
     * 获取默认支持的文件扩展名列表
     */
    private static function getDefaultSupportedExts(): array
    {
        return [
            'azw', 'azw1', 'azw3', 'azw4', 'cb7', 'cbc', 'cbr', 'cbz',
            'chm', 'docx', 'epub', 'fb2', 'fbz', 'html', 'htmlz', 'imp',
            'kepub', 'lit', 'lrf', 'lrx', 'mobi', 'odt', 'oebzip', 'opf',
            'pdb', 'pdf', 'pml', 'pmlz', 'pobi', 'prc', 'rar', 'rb',
            'rtf', 'snb', 'tpz', 'txt', 'txtz', 'updb', 'zip'
        ];
    }
}

<?php

declare(strict_types=1);

/**
 * 自动加载类文件
 * 
 * 简化版的PSR-4自动加载器
 */

spl_autoload_register(function ($className) {
    // 处理命名空间
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../';

    // 检查类名是否以命名空间前缀开头
    $len = strlen($prefix);
    if (strncmp($prefix, $className, $len) !== 0) {
        return;
    }

    // 获取相对类名
    $relativeClass = substr($className, $len);

    // 将命名空间分隔符替换为目录分隔符
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // 如果文件存在，则加载它
    if (file_exists($file)) {
        require_once $file;
    }
});

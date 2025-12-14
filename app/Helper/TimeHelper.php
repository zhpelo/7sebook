<?php

declare(strict_types=1);

namespace App\Helper;

/**
 * 时间处理辅助类
 */
class TimeHelper
{
    /**
     * 获取当前时间（格式：Y-m-d H:i:s）
     * 
     * @return string 当前时间字符串
     */
    public static function getCurrentTime(): string
    {
        return date('Y-m-d H:i:s');
    }
}

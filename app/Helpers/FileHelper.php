<?php

namespace App\Helpers;

/**
 * 文件辅助函数
 */
class FileHelper
{
    /**
     * 将文件大小（字节）转换为人类可读格式
     * 
     * @param string|int|null $size 文件大小（字节数或已格式化的字符串）
     * @param int $precision 小数位数，默认2位
     * @return string 格式化后的文件大小，如 "1.5 MB"
     */
    public static function formatFileSize($size, int $precision = 2): string
    {
        if (empty($size)) {
            return '未知';
        }

        // 如果已经是格式化的字符串（包含单位），尝试解析
        if (is_string($size) && preg_match('/^([\d.]+)\s*(B|KB|MB|GB|TB)$/i', trim($size), $matches)) {
            $value = (float) $matches[1];
            $unit = strtoupper($matches[2]);
            
            // 转换为字节
            $bytes = self::convertToBytes($value, $unit);
        } else {
            // 尝试转换为数字（字节）
            $bytes = (int) preg_replace('/[^\d]/', '', (string) $size);
        }

        if ($bytes <= 0) {
            return '未知';
        }

        // 定义单位
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $unitIndex = 0;
        $size = (float) $bytes;

        // 循环除以1024，直到找到合适的单位
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        // 格式化输出
        return number_format($size, $precision, '.', '') . ' ' . $units[$unitIndex];
    }

    /**
     * 将带单位的文件大小转换为字节
     * 
     * @param float $value 数值
     * @param string $unit 单位 (B, KB, MB, GB, TB)
     * @return int 字节数
     */
    private static function convertToBytes(float $value, string $unit): int
    {
        $unit = strtoupper($unit);
        $multipliers = [
            'B' => 1,
            'KB' => 1024,
            'MB' => 1024 * 1024,
            'GB' => 1024 * 1024 * 1024,
            'TB' => 1024 * 1024 * 1024 * 1024,
        ];

        return (int) ($value * ($multipliers[$unit] ?? 1));
    }
}

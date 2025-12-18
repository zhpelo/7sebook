<?php

declare(strict_types=1);

namespace App\Helper;

/**
 * 字符串处理辅助类
 */
class StringHelper
{
    /**
     * 将字节大小转换为人类可读的格式
     * 
     * @param int|string|null $bytes 字节数
     * @return string 格式化后的文件大小
     */
    public static function formatFileSize($bytes): string
    {
        if ($bytes === null || $bytes === '') {
            return '未知';
        }

        $bytes = (float)$bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        foreach ($units as $unit) {
            if ($bytes < 1024) {
                return round($bytes, 2) . ' ' . $unit;
            }
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' PB';
    }

    /**
     * 格式化作者信息，将多个作者分割并添加搜索链接
     * 
     * @param string|null $authors 作者字符串
     * @return string 格式化后的HTML
     */
    public static function formatAuthors(?string $authors): string
    {
        if (empty($authors)) {
            return '佚名';
        }

        // 使用多种分隔符分割作者名称
        $authorList = preg_split('/(;|,|；|，|、)/', $authors);
        $formattedAuthors = [];

        foreach ($authorList as $author) {
            $author = trim($author);
            if (!empty($author)) {
                $formattedAuthors[] = '<a href="?q=' . urlencode($author) . '&type=author" class="text-blue-600 hover:text-blue-800 hover:underline">' . htmlspecialchars($author) . '</a>';
            }
        }

        return implode(', ', $formattedAuthors);
    }

    /**
     * 高亮匹配的文本
     * 
     * @param string $text 原始文本
     * @param string $query 查询关键词
     * @return string 高亮后的HTML
     */
    public static function highlightMatches(string $text, string $query): string
    {
        if (empty($query) || empty($text)) {
            return $text;
        }

        $keywords = array_filter(explode(' ', $query), function ($k) {
            return strlen($k) > 0;
        });
        $result = $text;

        foreach ($keywords as $keyword) {
            if (strlen($keyword) > 0) {
                $escapedKeyword = preg_quote($keyword, '/');
                $result = preg_replace(
                    '/(' . $escapedKeyword . ')/i',
                    '<span class="bg-yellow-200">$1</span>',
                    $result
                );
            }
        }

        return $result;
    }

    /**
     * 清理文本为UTF-8编码
     * 
     * @param string $str 原始字符串
     * @param string $fromEncoding 源编码，'auto'为自动检测
     * @return string 清理后的UTF-8字符串
     */
    public static function cleanToUtf8(string $str, string $fromEncoding = 'auto'): string
    {
        if (mb_check_encoding($str, 'UTF-8')) {
            return $str;
        }

        $encoding = $fromEncoding === 'auto' 
            ? mb_detect_encoding($str, ['UTF-8', 'GBK', 'GB2312', 'ASCII', 'ISO-8859-1'], true)
            : $fromEncoding;

        if ($encoding && $encoding !== 'UTF-8') {
            $str = mb_convert_encoding($str, 'UTF-8', $encoding);
        } else {
            $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
        }

        return mb_encode_numericentity($str, [0x0080, 0xffff, 0, 0xffff], 'UTF-8');
    }
}

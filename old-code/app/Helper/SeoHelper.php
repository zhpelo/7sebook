<?php

declare(strict_types=1);

namespace App\Helper;

/**
 * SEO辅助类
 * 
 * 处理SEO相关的结构化数据生成，包括JSON-LD格式的数据
 */
class SeoHelper
{
    /**
     * 生成书籍结构化数据（JSON-LD格式）
     * 
     * @param array<string, mixed> $book 书籍数据数组
     * @return string JSON-LD格式的JSON字符串
     */
    public static function generateBookStructuredData(array $book): string
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Book',
            'name' => $book['title'] ?? '',
            'author' => !empty($book['author']) ? explode(',', $book['author']) : [],
            'description' => $book['description'] ?? '',
            'datePublished' => $book['published_at'] ?? '',
            'publisher' => $book['publisher'] ?? '',
            'inLanguage' => $book['languages'] ?? '',
            'isbn' => $book['identifiers'] ?? ''
        ];

        // 移除空值
        $data = array_filter($data, function ($value) {
            return !empty($value) && $value !== '暂无资料';
        });

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * 生成面包屑结构化数据（JSON-LD格式）
     * 
     * @param string $action 当前操作类型（如 'detail', 'search' 等）
     * @param array<string, mixed>|null $book 书籍数据（用于详情页）
     * @return string JSON-LD格式的JSON字符串
     */
    public static function generateBreadcrumbStructuredData(string $action, ?array $book = null): string
    {
        $items = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => '首页',
                'item' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/'
            ]
        ];

        if ($action === 'detail' && $book !== null) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $book['title'] ?? '书籍详情',
                'item' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/')
            ];
        } elseif (!empty($_GET['q'])) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => '搜索结果',
                'item' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/')
            ];
        }

        return json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

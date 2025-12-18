<?php

declare(strict_types=1);

namespace App\Helper;

/**
 * URL构建辅助类
 */
class UrlHelper
{
    /**
     * 构建分页URL
     * 
     * @param int $newPage 目标页码
     * @param array<string, mixed> $baseParams 基础参数数组
     * @param array<string> $preserveParams 需要保留的GET参数列表
     * @return string 分页URL
     */
    public static function buildPageUrl(
        int $newPage,
        array $baseParams = [],
        array $preserveParams = []
    ): string {
        // 合并基础参数
        $params = array_merge($baseParams, ['page' => $newPage]);

        // 处理需要保留的参数
        if (empty($preserveParams)) {
            // 如果未指定，保留所有非page参数
            foreach ($_GET as $key => $value) {
                if ($key !== 'page' && !isset($baseParams[$key])) {
                    $params[$key] = $value;
                }
            }
        } else {
            // 只保留指定的参数
            foreach ($preserveParams as $key) {
                if (isset($_GET[$key]) && !isset($baseParams[$key])) {
                    $params[$key] = $_GET[$key];
                }
            }
        }

        // 过滤空值（但保留0和'0'）
        $params = array_filter($params, function ($value) {
            return $value !== '' && $value !== null;
        });

        return '?' . http_build_query($params);
    }

    /**
     * 构建搜索页面URL
     * 
     * @param string $query 搜索关键词
     * @param string $type 搜索类型
     * @param int $newPage 页码
     * @return string URL
     */
    public static function buildSearchPageUrl(string $query, string $type, int $newPage): string
    {
        $baseParams = array_filter([
            'q' => $query,
            'type' => $type,
            'format' => $_GET['format'] ?? ''
        ], function ($value) {
            return !empty($value) || $value === 0;
        });

        return self::buildPageUrl($newPage, $baseParams);
    }

    /**
     * 构建管理页面URL
     * 
     * @param string $action 操作
     * @param int $newPage 页码
     * @param array<string, mixed> $additionalParams 额外参数
     * @return string URL
     */
    public static function buildAdminPageUrl(
        string $action,
        int $newPage,
        array $additionalParams = []
    ): string {
        $params = array_merge(['action' => $action, 'page' => $newPage], $additionalParams);
        return '?' . http_build_query($params);
    }
}

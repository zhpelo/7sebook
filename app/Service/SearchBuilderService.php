<?php

declare(strict_types=1);

namespace App\Service;

/**
 * 搜索构建服务类
 * 
 * 构建搜索条件的SQL语句
 */
class SearchBuilderService
{
    /**
     * 构建搜索条件
     * 
     * @param string $type 搜索类型
     * @param string $query 查询关键词
     * @param string $format 文件格式
     * @return array{where: string, relevance: string, params: array<string, string>}
     */
    public function buildSearchConditions(string $type, string $query, string $format = ''): array
    {
        $whereParts = [];
        $relevanceParts = [];
        $params = [];

        // 将查询字符串按空格分割为关键词
        $keywords = array_filter(explode(' ', $query), function ($k) {
            return strlen(trim($k)) > 0;
        });

        foreach ($keywords as $idx => $keyword) {
            $paramName = ':keyword' . $idx;
            $params[$paramName] = '%' . $keyword . '%';

            $fieldConditions = [];
            $relevanceExpressions = [];

            switch ($type) {
                case 'title':
                    $fieldConditions[] = "b.title LIKE :keyword{$idx}";
                    $relevanceExpressions[] = "CASE WHEN b.title LIKE :keyword{$idx} THEN 10 ELSE 0 END";
                    break;
                case 'author':
                    $fieldConditions[] = "b.author LIKE :keyword{$idx}";
                    $relevanceExpressions[] = "CASE WHEN b.author LIKE :keyword{$idx} THEN 8 ELSE 0 END";
                    break;
                case 'description':
                    $fieldConditions[] = "b.description LIKE :keyword{$idx}";
                    $relevanceExpressions[] = "CASE WHEN b.description LIKE :keyword{$idx} THEN 5 ELSE 0 END";
                    break;
                default:
                    $fieldConditions[] = "(b.title LIKE :keyword{$idx} OR b.author LIKE :keyword{$idx} OR b.description LIKE :keyword{$idx})";
                    $relevanceExpressions[] = "
                        CASE 
                            WHEN b.title LIKE :keyword{$idx} THEN 10
                            WHEN b.author LIKE :keyword{$idx} THEN 8
                            WHEN b.description LIKE :keyword{$idx} THEN 5
                            ELSE 0
                        END";
            }

            $whereParts[] = '(' . implode(' OR ', $fieldConditions) . ')';
            $relevanceParts[] = implode(' + ', $relevanceExpressions);
        }

        // 如果指定了文件格式，则添加格式筛选条件
        if (!empty($format)) {
            $whereParts[] = "b.file_format = :format";
            $params[':format'] = $format;
        }

        $whereClause = implode(' AND ', $whereParts);
        $relevanceClause = implode(' + ', $relevanceParts);

        return [
            'where' => $whereClause,
            'relevance' => $relevanceClause,
            'params' => $params
        ];
    }
}

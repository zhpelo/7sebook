-- 电子书数据表结构（优化版 - 支持千万级数据）
-- 创建 books 表

CREATE TABLE IF NOT EXISTS `books` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `title` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '书名',
  `author` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '作者',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '简介描述',
  `cover_url` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '封面URL',
  `published_at` date DEFAULT NULL COMMENT '出版日期',
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '分类',
  `isbn` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ISBN编号',
  `publisher` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '出版社',
  `page_count` int unsigned DEFAULT NULL COMMENT '页数',
  `languages` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '中文' COMMENT '语言',
  `identifiers` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '唯一标识符（如ASIN、ISBN）',
  `file_name` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件名',
  `file_path` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件路径',
  `file_size` bigint unsigned NOT NULL DEFAULT 0 COMMENT '文件大小（字节）',
  `file_format` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件格式',
  `file_md5` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件MD5值',
  `rating` decimal(3,2) unsigned DEFAULT '0.00' COMMENT '评分',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  -- 唯一索引：文件MD5，避免重复文件
  UNIQUE KEY `uk_file_md5` (`file_md5`),
  -- 单列索引：用于精确查询和排序
  KEY `idx_file_format` (`file_format`),
  KEY `idx_category` (`category`),
  KEY `idx_published_at` (`published_at`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_updated_at` (`updated_at`),
  KEY `idx_publisher` (`publisher`(100)),
  KEY `idx_isbn` (`isbn`),
  -- 复合索引：优化常用查询组合
  KEY `idx_format_created` (`file_format`, `created_at`),
  KEY `idx_category_created` (`category`, `created_at`),
  KEY `idx_cover_created` (`cover_url`(255), `created_at`),
  -- 全文索引：优化全文搜索性能
  -- MySQL 5.7+ 支持中文全文索引需要 ngram 解析器
  -- MySQL 8.0+ 默认支持中文全文索引
  -- 如果 MySQL 版本 < 5.7，请移除 WITH PARSER ngram
  -- 配置参数：innodb_ft_min_token_size = 1 (MySQL 5.7+) 或 ft_min_word_len = 1 (MyISAM)
  FULLTEXT KEY `ft_title` (`title`) WITH PARSER ngram,
  FULLTEXT KEY `ft_author` (`author`) WITH PARSER ngram,
  FULLTEXT KEY `ft_description` (`description`) WITH PARSER ngram,
  FULLTEXT KEY `ft_title_author` (`title`, `author`) WITH PARSER ngram,
  -- 覆盖索引：优化常见查询，避免回表
  KEY `idx_search_cover` (`id`, `title`, `author`, `cover_url`(255), `file_format`, `file_size`)
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  COMMENT='电子书数据表'
  -- 表级优化参数
  ROW_FORMAT=DYNAMIC
  KEY_BLOCK_SIZE=8;

-- 如果 MySQL 版本支持，可以添加以下注释以优化 InnoDB 全文索引
-- ALTER TABLE `books` MODIFY `title` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '书名' WITH PARSER ngram;
-- ALTER TABLE `books` MODIFY `author` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '作者' WITH PARSER ngram;

-- 性能优化建议：
-- 1. 定期执行 ANALYZE TABLE books; 更新表统计信息
-- 2. 对于千万级数据，考虑使用分区表（按年份或ID范围分区）
-- 3. 如果 MySQL 版本 >= 8.0，考虑使用 JSON 列存储额外的元数据
-- 4. 定期优化表：OPTIMIZE TABLE books;

-- 分区表示例（可选，适用于超大数据量）：
-- CREATE TABLE `books` (
--   ... (字段定义同上) ...
-- ) ENGINE=InnoDB
--   DEFAULT CHARSET=utf8mb4
--   COLLATE=utf8mb4_unicode_ci
--   PARTITION BY RANGE (`id`) (
--     PARTITION p0 VALUES LESS THAN (2000000),
--     PARTITION p1 VALUES LESS THAN (4000000),
--     PARTITION p2 VALUES LESS THAN (6000000),
--     PARTITION p3 VALUES LESS THAN (8000000),
--     PARTITION p4 VALUES LESS THAN (10000000),
--     PARTITION pmax VALUES LESS THAN MAXVALUE
--   );

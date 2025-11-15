-- 电子书标签数据表结构
-- 如果表已存在，请先检查现有结构，然后根据需要进行调整

-- 创建 book_tags 表（与优化后的 books 表匹配）
CREATE TABLE IF NOT EXISTS `book_tags` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '标签ID',
    `book_id` bigint unsigned NOT NULL COMMENT '电子书ID',
    `tag_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '标签名称',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    PRIMARY KEY (`id`),
    KEY `idx_book_id` (`book_id`),
    KEY `idx_tag_name` (`tag_name`),
    KEY `idx_created_at` (`created_at`),
    -- 复合索引优化常用查询
    KEY `idx_book_tag` (`book_id`, `tag_name`),
    KEY `idx_tag_book` (`tag_name`, `book_id`),
    CONSTRAINT `fk_book_tags_book_id` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci 
  COMMENT='电子书标签表'
  ROW_FORMAT=DYNAMIC;



-- 如果表已存在但缺少 created_at 字段，可以使用以下 SQL 添加
-- ALTER TABLE `book_tags` ADD COLUMN `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间' AFTER `tag_name`;

-- 如果表已存在但缺少外键，可以使用以下 SQL 添加
-- ALTER TABLE `book_tags` ADD CONSTRAINT `fk_book_tags_book_id` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 创建索引以优化查询性能
-- CREATE INDEX `idx_book_tag` ON `book_tags` (`book_id`, `tag_name`);
-- CREATE INDEX `idx_tag_book` ON `book_tags` (`tag_name`, `book_id`);


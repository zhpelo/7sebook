-- books 表结构迁移脚本（从旧结构升级到优化后的结构）
-- 执行前请备份数据！
-- 
-- 修复外键约束不兼容问题
-- 错误原因：book_tags.book_id (int) 和 books.id (bigint) 类型不匹配
-- 解决方法：先删除外键，修改 book_tags.book_id，再修改 books.id，最后重新添加外键

-- ============================================
-- 第一部分：处理外键约束和ID类型
-- ============================================

-- 步骤 1：删除 book_tags 表的外键约束
-- 注意：如果你的 MySQL 版本不支持 IF EXISTS，请先执行以下查询获取外键名称：
-- SHOW CREATE TABLE `book_tags`;
-- 或者查询：
-- SELECT CONSTRAINT_NAME 
-- FROM information_schema.TABLE_CONSTRAINTS 
-- WHERE TABLE_SCHEMA = DATABASE() 
-- AND TABLE_NAME = 'book_tags' 
-- AND CONSTRAINT_TYPE = 'FOREIGN KEY';

-- MySQL 8.0+ 支持 IF EXISTS
ALTER TABLE `book_tags` DROP FOREIGN KEY IF EXISTS `fk_book_tags_book_id`;

-- MySQL 5.7 及以下版本，如果上面报错，请手动执行：
-- 先查询外键名称（替换为实际的外键名称）
-- ALTER TABLE `book_tags` DROP FOREIGN KEY `fk_book_tags_book_id`;

-- 步骤 2：修改 book_tags 表的 book_id 为 bigint（必须先修改，再修改 books.id）
-- 如果 book_tags 表不存在，可以跳过此步骤，或者先注释掉
ALTER TABLE `book_tags` MODIFY `book_id` bigint unsigned NOT NULL COMMENT '电子书ID';

-- 步骤 3：修改 books 表的主键ID类型为 bigint（支持更大数据量）
ALTER TABLE `books` MODIFY `id` bigint unsigned NOT NULL AUTO_INCREMENT;

-- 步骤 4：重新添加外键约束
-- 确保 book_tags 表存在且 book_id 已经是 bigint 类型
ALTER TABLE `book_tags` 
ADD CONSTRAINT `fk_book_tags_book_id` 
FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- ============================================
-- 第二部分：优化 books 表字段类型
-- ============================================

-- 2. 优化字段类型
ALTER TABLE `books` MODIFY `title` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '书名';
ALTER TABLE `books` MODIFY `author` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '作者';
ALTER TABLE `books` MODIFY `cover_url` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '封面URL';
ALTER TABLE `books` MODIFY `publisher` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '出版社';
ALTER TABLE `books` MODIFY `isbn` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ISBN编号';
ALTER TABLE `books` MODIFY `identifiers` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '唯一标识符';
ALTER TABLE `books` MODIFY `file_name` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件名';
ALTER TABLE `books` MODIFY `file_path` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件路径';

-- 3. 转换 file_size 从 varchar 到 bigint（如果是字符串类型，需要先清理数据）
-- 注意：如果 file_size 存储的是 "1.5MB" 这样的字符串，需要先转换为字节数
-- 执行前请检查 file_size 的数据格式，如果是字符串格式，请先执行以下转换：
/*
UPDATE `books` SET `file_size` = CAST(REPLACE(REPLACE(REPLACE(REPLACE(`file_size`, 'KB', ''), 'MB', ''), 'GB', ''), 'TB', '') AS UNSIGNED) * 
  CASE 
    WHEN `file_size` LIKE '%TB%' THEN 1099511627776
    WHEN `file_size` LIKE '%GB%' THEN 1073741824
    WHEN `file_size` LIKE '%MB%' THEN 1048576
    WHEN `file_size` LIKE '%KB%' THEN 1024
    ELSE 1
  END
WHERE `file_size` REGEXP '^[0-9]+';
*/
ALTER TABLE `books` MODIFY `file_size` bigint unsigned NOT NULL DEFAULT 0 COMMENT '文件大小（字节）';

-- 4. 优化 page_count 类型
ALTER TABLE `books` MODIFY `page_count` int unsigned DEFAULT NULL COMMENT '页数';

-- 5. 优化 rating 类型
ALTER TABLE `books` MODIFY `rating` decimal(3,2) unsigned DEFAULT '0.00' COMMENT '评分';

-- 6. 优化时间字段（统一使用 datetime）
ALTER TABLE `books` MODIFY `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间';
ALTER TABLE `books` MODIFY `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间';

-- 7. 删除 publish_date 字段（如果存在且与 published_at 重复）
-- ALTER TABLE `books` DROP COLUMN `publish_date`;

-- ============================================
-- 第三部分：优化索引
-- ============================================

-- 8. 添加唯一索引（文件MD5）- 如果已存在会报错，可以忽略
-- ALTER TABLE `books` ADD UNIQUE KEY `uk_file_md5` (`file_md5`);

-- 9. 删除冗余的单列索引（全文索引已经覆盖）- 如果不存在会报错，可以忽略
-- ALTER TABLE `books` DROP INDEX IF EXISTS `idx_title`;
-- ALTER TABLE `books` DROP INDEX IF EXISTS `idx_author`;

-- 10. 添加优化的索引（如果已存在会报错，可以使用 IGNORE 或先检查）
ALTER TABLE `books` ADD KEY `idx_file_format` (`file_format`);
ALTER TABLE `books` ADD KEY `idx_category` (`category`);
ALTER TABLE `books` ADD KEY `idx_published_at` (`published_at`);
ALTER TABLE `books` ADD KEY `idx_created_at` (`created_at`);
ALTER TABLE `books` ADD KEY `idx_updated_at` (`updated_at`);
ALTER TABLE `books` ADD KEY `idx_publisher` (`publisher`(100));
ALTER TABLE `books` ADD KEY `idx_isbn` (`isbn`);

-- 11. 添加复合索引
ALTER TABLE `books` ADD KEY `idx_format_created` (`file_format`, `created_at`);
ALTER TABLE `books` ADD KEY `idx_category_created` (`category`, `created_at`);
ALTER TABLE `books` ADD KEY `idx_cover_created` (`cover_url`(255), `created_at`);

-- 12. 添加覆盖索引（优化常见查询，避免回表）
ALTER TABLE `books` ADD KEY `idx_search_cover` (`id`, `title`(255), `author`(255), `cover_url`(255), `file_format`, `file_size`);

-- 13. 优化全文索引（如果 MySQL 版本 >= 5.7）
-- 先删除旧的全文索引（如果不存在会报错，可以忽略）
-- ALTER TABLE `books` DROP INDEX IF EXISTS `idx_title_fulltext`;
-- ALTER TABLE `books` DROP INDEX IF EXISTS `idx_author_fulltext`;
-- ALTER TABLE `books` DROP INDEX IF EXISTS `idx_description_fulltext`;
-- ALTER TABLE `books` DROP INDEX IF EXISTS `idx_title_author_desc_fulltext`;

-- 添加新的全文索引（支持中文）
ALTER TABLE `books` ADD FULLTEXT KEY `ft_title` (`title`) WITH PARSER ngram;
ALTER TABLE `books` ADD FULLTEXT KEY `ft_author` (`author`) WITH PARSER ngram;
ALTER TABLE `books` ADD FULLTEXT KEY `ft_description` (`description`) WITH PARSER ngram;
ALTER TABLE `books` ADD FULLTEXT KEY `ft_title_author` (`title`, `author`) WITH PARSER ngram;

-- ============================================
-- 第四部分：优化表结构
-- ============================================

-- 14. 优化表结构
ALTER TABLE `books` ROW_FORMAT=DYNAMIC;

-- 15. 更新表统计信息
ANALYZE TABLE `books`;

-- 16. 优化表（可选，但会锁表，建议在低峰期执行）
-- OPTIMIZE TABLE `books`;


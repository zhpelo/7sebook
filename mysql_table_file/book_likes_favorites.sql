-- 书籍点赞表
DROP TABLE IF EXISTS `book_likes`;
CREATE TABLE `book_likes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '点赞ID',
  `user_id` int NOT NULL COMMENT '用户ID',
  `book_id` bigint unsigned NOT NULL COMMENT '书籍ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '点赞时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_book` (`user_id`, `book_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_book_id` (`book_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_book_likes_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_book_likes_book_id` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='书籍点赞表';

-- 书籍收藏表
DROP TABLE IF EXISTS `book_favorites`;
CREATE TABLE `book_favorites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '收藏ID',
  `user_id` int NOT NULL COMMENT '用户ID',
  `book_id` bigint unsigned NOT NULL COMMENT '书籍ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '收藏时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_book` (`user_id`, `book_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_book_id` (`book_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_book_favorites_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_book_favorites_book_id` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='书籍收藏表';

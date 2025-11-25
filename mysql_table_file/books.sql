-- 电子书数据表结构（优化版 - 支持千万级数据）
-- 创建 books 表
-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2025-11-26 07:26:39
-- 服务器版本： 5.7.43-log
-- PHP 版本： 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- 表的结构 `books`
--

CREATE TABLE `books` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `cover_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publish_date` date DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isbn` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publisher` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_at` datetime DEFAULT NULL COMMENT '出版日期',
  `page_count` int(11) DEFAULT NULL,
  `languages` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '中文' COMMENT '语言',
  `identifiers` text COLLATE utf8mb4_unicode_ci COMMENT '唯一标识符，如 ASIN、ISBN',
  `price` decimal(10,2) DEFAULT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '文件名',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_format` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_md5` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `rating` decimal(3,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转储表的索引
--

--
-- 表的索引 `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_title` (`title`),
  ADD KEY `idx_author` (`author`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_publish_date` (`publish_date`),
  ADD KEY `idx_isbn` (`isbn`),
  ADD KEY `idx_publisher` (`publisher`);
ALTER TABLE `books` ADD FULLTEXT KEY `idx_title_fulltext` (`title`);
ALTER TABLE `books` ADD FULLTEXT KEY `idx_author_fulltext` (`author`);
ALTER TABLE `books` ADD FULLTEXT KEY `idx_description_fulltext` (`description`);
ALTER TABLE `books` ADD FULLTEXT KEY `idx_title_author_desc_fulltext` (`title`,`author`,`description`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `books`
--
ALTER TABLE `books`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;


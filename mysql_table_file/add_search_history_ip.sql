-- 为 search_history 表添加 ip_address 字段
ALTER TABLE `search_history` 
ADD COLUMN `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址' AFTER `user_id`,
ADD KEY `idx_ip_address` (`ip_address`);


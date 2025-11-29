-- 为download_logs表添加IP地址字段
-- 用于加强下载限制，防止用户通过换账号等方式绕过限制

-- 添加IP地址字段
ALTER TABLE `download_logs` 
ADD COLUMN `ip_address` VARCHAR(45) NULL DEFAULT NULL AFTER `download_time`,
ADD INDEX `idx_ip_address` (`ip_address`),
ADD INDEX `idx_user_ip_date` (`user_id`, `ip_address`, `download_time`);

-- 为现有记录设置默认IP（可选，如果需要的话）
-- UPDATE download_logs SET ip_address = '0.0.0.0' WHERE ip_address IS NULL;


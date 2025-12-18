# WebP 图片优化功能说明

## 功能概述

此功能自动将原图转换为 WebP 格式并缩放至 200px 宽度，大幅提升网站图片加载速度。

## 工作原理

1. **路径转换**：
   - 原图路径：`/covers/251119/46af8b1fa16c97986007e8dcdfd63fd8.jpg`
   - 优化路径：`/covers_sm/251119/46af8b1fa16c97986007e8dcdfd63fd8.webp`

2. **自动生成**：
   - 用户访问优化后的图片 URL
   - Nginx 检查文件是否存在
   - 如果不存在，调用 `generate_webp.php` 自动生成
   - 生成后保存到 `/covers_sm/` 目录，下次直接使用

## 文件说明

### 1. ImageService.php
图片处理服务类，提供以下方法：
- `convertToWebP()`: 将图片转换为 WebP 格式并缩放
- `getOptimizedPath()`: 从原图路径生成优化路径
- `ensureOptimizedImage()`: 确保优化图片存在，不存在则生成

### 2. generate_webp.php
Nginx 调用的图片生成脚本，自动处理图片转换。

### 3. ViewHelper.php
视图辅助类，新增 `getOptimizedImageUrl()` 方法用于生成优化后的图片 URL。

## Nginx 配置

### 方法一：使用 try_files（推荐）

```nginx
location ~ ^/covers_sm/(.+\.webp)$ {
    # 首先尝试直接提供文件
    try_files $uri @generate_webp;
    
    # 设置缓存头
    expires 1y;
    add_header Cache-Control "public, immutable";
    add_header Content-Type "image/webp";
}

# 如果文件不存在，调用 PHP 脚本生成
location @generate_webp {
    # 重写 URL 到生成脚本
    rewrite ^/covers_sm/(.+\.webp)$ /generate_webp.php?path=$1 last;
}

# PHP 处理
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php-fpm.sock;  # 根据您的环境调整
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
    fastcgi_param REQUEST_URI $request_uri;
}
```

### 方法二：直接 rewrite

```nginx
location /covers_sm/ {
    try_files $uri @webp_generator;
}

location @webp_generator {
    rewrite ^/covers_sm/(.+)$ /generate_webp.php?path=$1 last;
}
```

## 使用方式

### 在视图文件中使用

```php
<?php
use App\Helper\ViewHelper;

// 获取优化后的图片 URL
$optimizedUrl = ViewHelper::getOptimizedImageUrl($book['cover_url']);
?>

<img src="<?php echo htmlspecialchars($optimizedUrl); ?>" alt="封面" loading="lazy">
```

## 系统要求

1. **PHP 扩展**：
   - GD 扩展（必须）
   - WebP 支持（PHP 7.1+ 默认支持）

2. **目录权限**：
   - 确保 PHP 有权限在 `/covers_sm/` 目录创建文件和目录
   - 建议设置目录权限为 755，文件权限为 644

3. **Nginx 配置**：
   - 确保 PHP-FPM 正常运行
   - 确保 fastcgi 配置正确

## 性能优化建议

1. **批量生成**：可以编写脚本批量生成所有图片，避免首次访问时的延迟
2. **CDN 缓存**：如果使用 CDN，将 `/covers_sm/` 目录同步到 CDN
3. **监控**：监控 `generate_webp.php` 的执行时间，如果太慢可以考虑异步生成

## 故障排查

### 问题：图片无法生成

1. 检查 PHP GD 扩展是否安装：
   ```bash
   php -m | grep gd
   ```

2. 检查 WebP 支持：
   ```bash
   php -r "var_dump(function_exists('imagewebp'));"
   ```

3. 检查目录权限：
   ```bash
   ls -la covers_sm/
   chmod 755 covers_sm/
   ```

4. 查看 PHP 错误日志：
   ```bash
   tail -f /var/log/php-fpm/error.log
   ```

### 问题：Nginx 返回 404

1. 检查 Nginx 配置是否正确
2. 检查 PHP-FPM 是否正常运行
3. 查看 Nginx 错误日志：
   ```bash
   tail -f /var/log/nginx/error.log
   ```

## 注意事项

1. 首次访问优化图片时会有一定延迟（需要生成）
2. 生成后的图片会永久保存，不会自动删除
3. 如果原图更新，需要手动删除对应的优化图片
4. 建议定期清理未使用的优化图片以节省空间

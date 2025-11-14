<?php
// 生成 Google Sitemap 文件

require 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('数据库连接失败');
}

// 设置响应头
header('Content-Type: application/xml; charset=utf-8');

// 输出 XML 头部
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// 获取所有电子书信息
$stmt = $pdo->query("SELECT id, updated_at FROM books ORDER BY id ASC");
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 添加主页
echo "  <url>\n";
echo "    <loc>http://" . $_SERVER['HTTP_HOST'] . "/</loc>\n";
echo "    <changefreq>daily</changefreq>\n";
echo "    <priority>1.0</priority>\n";
echo "  </url>\n";

// 为每本电子书添加 URL 条目
foreach ($books as $book) {
    $url = "https://" . $_SERVER['HTTP_HOST'] . "/?action=detail&amp;id=" . $book['id'];
    $lastmod = date('Y-m-d', strtotime($book['updated_at'] ?: $book['created_at']));
    
    echo "  <url>\n";
    echo "    <loc>" . $url . "</loc>\n";
    echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    echo "  </url>\n";
}

// 结束标签
echo "</urlset>\n";
?>
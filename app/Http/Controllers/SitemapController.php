<?php

namespace App\Http\Controllers;

use App\Models\Book;

class SitemapController extends Controller
{
    /**
     * 生成 sitemap.xml 文件
     */
    public function generateSitemap()
    {
        $sitemapPath = public_path('sitemap.xml');

        try {
            // 获取基础URL
            $baseUrl = url('/');

            // 开始构建XML内容
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            // 添加主页
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($baseUrl . '/', ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "    <changefreq>daily</changefreq>\n";
            $xml .= "    <priority>1.0</priority>\n";
            $xml .= "  </url>\n";

            // 获取所有电子书信息
            $books = Book::select('id', 'updated_at', 'created_at')
                ->orderByDesc('id')
                ->limit(10000)
                ->get();

            // 为每本电子书添加URL条目
            foreach ($books as $book) {
                $url = $baseUrl . '/book/' . $book->id;
                $lastmod = $book->updated_at ? $book->updated_at->format('Y-m-d') : $book->created_at->format('Y-m-d');

                $xml .= "  <url>\n";
                $xml .= "    <loc>" . htmlspecialchars($url, ENT_XML1, 'UTF-8') . "</loc>\n";
                $xml .= "    <lastmod>" . htmlspecialchars($lastmod, ENT_XML1, 'UTF-8') . "</lastmod>\n";
                $xml .= "    <changefreq>monthly</changefreq>\n";
                $xml .= "    <priority>0.8</priority>\n";
                $xml .= "  </url>\n";
            }

            // 结束标签
            $xml .= "</urlset>\n";

            // 写入文件
            // file_put_contents($sitemapPath, $xml);

            return response($xml, 200)
                ->header('Content-Type', 'application/xml; charset=utf-8');
        } catch (\Exception $e) {
            return response('生成 sitemap 失败: ' . $e->getMessage(), 500);
        }
    }
}

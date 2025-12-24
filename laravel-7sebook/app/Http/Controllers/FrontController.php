<?php

namespace App\Http\Controllers;

/**
 * FrontController 已拆分
 * 
 * 所有功能已按模块拆分到以下控制器：
 * - AuthController: 登录、注册、登出、忘记密码、重置密码
 * - BookController: 书籍详情、下载、标签书籍列表
 * - SearchController: 首页/搜索、热门搜索
 * - RankingController: 下载排行、最新书籍
 * - UserController: 个人资料、我的点赞、我的收藏
 * - LikeController: 点赞、取消点赞、收藏、取消收藏
 * - SitemapController: 生成sitemap
 * 
 * 此文件保留作为兼容入口，所有路由已更新指向新的控制器。
 * 如需删除此文件，请确保所有路由都已正确更新。
 */
class FrontController extends Controller
{
    // 所有方法已迁移到对应的控制器
}

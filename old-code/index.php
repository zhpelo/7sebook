<?php

declare(strict_types=1);

/**
 * 应用单入口文件
 * 
 * 所有请求都通过此文件处理，根据路由分发到相应的控制器
 */

// 加载自动加载器
require_once __DIR__ . '/app/Bootstrap/autoload.php';

// 初始化应用
use App\Bootstrap\Bootstrap;
use App\Router\Router;

Bootstrap::init();
Bootstrap::loadClasses();
Bootstrap::validateDatabase();

// 创建路由器
$router = new Router();

// 注册路由（如果需要自定义路由）
// $router->addRoute('GET', '/user/{id}', [UserController::class, 'show']);

// 分发请求
$router->dispatch();

<?php

declare(strict_types=1);

namespace App\Router;

/**
 * 路由处理类
 * 
 * 负责解析请求并分发到对应的控制器
 */
class Router
{
    private array $routes = [];

    /**
     * 添加路由
     * 
     * @param string $method HTTP方法
     * @param string $path 路径模式
     * @param callable|string $handler 处理函数或控制器类名
     */
    public function addRoute(string $method, string $path, $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
        ];
    }

    /**
     * 处理当前请求
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $this->getCurrentPath();

        // 处理 sitemap.xml 直接访问
        if ($path === '/sitemap.xml') {
            $this->handleSitemapXml();
            return;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if ($this->matchPath($route['path'], $path, $params)) {
                $this->executeHandler($route['handler'], $params);
                return;
            }
        }

        // 默认路由：根据action参数分发
        $this->handleDefaultRoute();
    }

    /**
     * 获取当前请求路径
     */
    private function getCurrentPath(): string
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($path, PHP_URL_PATH);
        return $path ?: '/';
    }

    /**
     * 匹配路径
     * 
     * @param string $pattern 路径模式
     * @param string $path 实际路径
     * @param array $params 输出参数
     * @return bool
     */
    private function matchPath(string $pattern, string $path, array &$params): bool
    {
        // 简单的路径匹配（可以根据需要扩展为正则表达式匹配）
        if ($pattern === $path) {
            return true;
        }

        // 支持简单的参数匹配，如 /user/{id}
        $patternParts = explode('/', trim($pattern, '/'));
        $pathParts = explode('/', trim($path, '/'));

        if (count($patternParts) !== count($pathParts)) {
            return false;
        }

        $params = [];
        foreach ($patternParts as $index => $part) {
            if (strpos($part, '{') === 0 && strpos($part, '}') === strlen($part) - 1) {
                $paramName = substr($part, 1, -1);
                $params[$paramName] = $pathParts[$index] ?? null;
            } elseif ($part !== $pathParts[$index]) {
                return false;
            }
        }

        return true;
    }

    /**
     * 执行处理函数
     */
    private function executeHandler($handler, array $params = []): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
        } elseif (is_string($handler) && class_exists($handler)) {
            $controller = new $handler();
            if (method_exists($controller, 'handle')) {
                $controller->handle($params);
            }
        }
    }

    /**
     * 处理默认路由（基于action参数）
     */
    private function handleDefaultRoute(): void
    {
        $action = $_GET['action'] ?? 'index';
        $controllerName = $this->determineController($action);
        
        if ($controllerName && class_exists($controllerName)) {
            $controller = new $controllerName();
            if (method_exists($controller, 'handle')) {
                $controller->handle(['action' => $action]);
            } else {
                $this->handle404();
            }
        } else {
            $this->handle404();
        }
    }

    /**
     * 根据action确定控制器
     */
    private function determineController(string $action): ?string
    {
        // 检查是否是管理后台相关的action
        if ($this->isAdminAction($action)) {
            return \App\Controllers\AdminController::class;
        }

        return \App\Controllers\FrontController::class;
    }

    /**
     * 判断是否是管理后台action
     */
    private function isAdminAction(string $action): bool
    {
        $adminActions = [
            'dashboard', 'users', 'books', 'downloads', 'search_history', 'tags',
            'add_user', 'edit_user', 'delete_user', 'batch_delete_users',
            'add_book', 'edit_book', 'delete_book', 'batch_delete_books',
            'add_tag', 'edit_tag', 'delete_tag', 'merge_tags', 'view_tag_books',
            'admin_settings', 'logout'
        ];
        return in_array($action, $adminActions, true);
    }

    /**
     * 处理 sitemap.xml 直接访问
     */
    private function handleSitemapXml(): void
    {
        $sitemapPath = __DIR__ . '/../sitemap.xml';
        
        // 如果文件存在，直接输出
        if (file_exists($sitemapPath)) {
            header('Content-Type: application/xml; charset=utf-8');
            readfile($sitemapPath);
            return;
        }
        
        // 如果文件不存在，尝试生成
        try {
            $controller = new \App\Controllers\FrontController();
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('generateSitemap');
            $method->setAccessible(true);
            $success = $method->invoke($controller, false);
            
            // 生成后再次尝试读取
            if ($success && file_exists($sitemapPath)) {
                header('Content-Type: application/xml; charset=utf-8');
                readfile($sitemapPath);
                return;
            }
        } catch (\Exception $e) {
            // 生成失败，返回404
        }
        
        // 文件不存在且无法生成，返回404
        http_response_code(404);
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<error>Sitemap not found</error>' . "\n";
    }

    /**
     * 处理404错误
     */
    private function handle404(): void
    {
        http_response_code(404);
        echo '<h1>404 Not Found</h1>';
        echo '<p>The requested page could not be found.</p>';
    }
}

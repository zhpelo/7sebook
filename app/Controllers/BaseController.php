<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * 控制器基类
 * 
 * 提供控制器共用的功能
 */
abstract class BaseController
{
    /**
     * 处理请求
     * 
     * @param array $params 路由参数
     */
    abstract public function handle(array $params = []): void;

    /**
     * 获取GET参数
     * 
     * @param string $key 参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * 获取POST参数
     * 
     * @param string $key 参数名
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * 获取请求方法
     */
    protected function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * 是否是POST请求
     */
    protected function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * 是否是GET请求
     */
    protected function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    /**
     * 重定向
     * 
     * @param string $url 目标URL
     * @param int $code HTTP状态码
     */
    protected function redirect(string $url, int $code = 302): void
    {
        header('Location: ' . $url, true, $code);
        exit;
    }

    /**
     * 返回JSON响应
     * 
     * @param array $data 数据
     * @param int $code HTTP状态码
     */
    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * 渲染视图
     * 
     * @param string $view 视图文件名（不包含扩展名）
     * @param array $data 传递给视图的数据
     * @param string|null $layout 布局文件名（不包含扩展名），null表示不使用布局
     */
    protected function render(string $view, array $data = [], ?string $layout = null): void
    {
        // 提取数据到变量
        extract($data);

        // 确定视图文件路径
        $viewFile = $this->getViewPath($view);
        
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("视图文件不存在: {$viewFile}");
        }

        // 如果指定了布局，使用布局
        if ($layout !== null) {
            $layoutFile = $this->getViewPath($layout);
            if (!file_exists($layoutFile)) {
                throw new \RuntimeException("布局文件不存在: {$layoutFile}");
            }
            
            // 捕获视图内容
            ob_start();
            require $viewFile;
            $content = ob_get_clean();
            
            // 在布局中渲染
            require $layoutFile;
        } else {
            // 直接渲染视图
            require $viewFile;
        }
    }

    /**
     * 获取视图文件路径
     */
    protected function getViewPath(string $view): string
    {
        $controllerName = str_replace('Controller', '', basename(get_class($this)));
        $viewDir = __DIR__ . '/../Views/' . strtolower($controllerName);
        
        // 如果视图文件不存在，尝试通用视图目录
        $viewFile = $viewDir . '/' . $view . '.php';
        if (!file_exists($viewFile)) {
            $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        }
        
        return $viewFile;
    }

    /**
     * 渲染带布局的视图（Admin专用）
     */
    protected function renderWithLayout(string $view, array $data = [], string $layout = 'admin/layout'): void
    {
        // 提取数据到变量
        extract($data);

        // 确定视图文件路径（从 Controllers 目录到 Views 目录是 ../Views/）
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            // 尝试使用 getViewPath
            $viewFile = $this->getViewPath($view);
        }
        
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("视图文件不存在: {$viewFile}");
        }

        // 确定布局文件路径
        $layoutFile = __DIR__ . '/../Views/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("布局文件不存在: {$layoutFile}");
        }
        
        // 捕获视图内容
        ob_start();
        require $viewFile;
        $content = ob_get_clean();
        
        // 在布局中渲染
        require $layoutFile;
    }

    /**
     * 获取当前页码
     */
    protected function getPage(): int
    {
        return max(1, (int)($this->get('page', 1)));
    }

    /**
     * 获取每页数量
     */
    protected function getLimit(int $default = 20): int
    {
        return max(1, (int)($this->get('limit', $default)));
    }
}

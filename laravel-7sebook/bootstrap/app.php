<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth' => \App\Http\Middleware\AuthMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 处理CSRF token验证失败（419错误）
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->is('login', 'register', 'forgot-password', 'reset-password')) {
                return redirect()->back()
                    ->with('error', '页面已过期，请刷新页面后重试')
                    ->withInput($request->except('password', '_token'));
            }
            
            // 其他POST请求返回419错误页面
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '页面已过期，请刷新页面后重试',
                    'error' => 'CSRF token mismatch'
                ], 419);
            }
            
            return response()->view('errors.419', [], 419);
        });
    })->create();

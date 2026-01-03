<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Auth\CustomUserProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 注册自定义用户提供者以支持 username 和 password_hash
        Auth::provider('custom', function ($app, array $config) {
            return new CustomUserProvider();
        });
    }
}

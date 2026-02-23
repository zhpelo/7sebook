<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use App\Auth\CustomUserProvider;
use App\Models\AppSetting;

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

        $this->applyDynamicMailConfig();
    }

    private function applyDynamicMailConfig(): void
    {
        try {
            if (!Schema::hasTable('app_settings')) {
                return;
            }

            $smtp = AppSetting::getValues([
                'smtp_host' => '',
                'smtp_port' => '465',
                'smtp_username' => '',
                'smtp_password' => '',
                'smtp_encryption' => 'ssl',
                'smtp_from_address' => '',
                'smtp_from_name' => '七秒电子书',
            ]);

            if (trim((string) $smtp['smtp_host']) === '' || trim((string) $smtp['smtp_username']) === '') {
                return;
            }

            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', trim((string) $smtp['smtp_host']));
            Config::set('mail.mailers.smtp.port', (int) $smtp['smtp_port']);
            Config::set('mail.mailers.smtp.username', trim((string) $smtp['smtp_username']));
            Config::set('mail.mailers.smtp.password', (string) $smtp['smtp_password']);

            $scheme = (string) $smtp['smtp_encryption'];
            Config::set('mail.mailers.smtp.scheme', $scheme === 'none' ? null : $scheme);

            $fromAddress = trim((string) $smtp['smtp_from_address']);
            $fromName = trim((string) $smtp['smtp_from_name']);

            if ($fromAddress !== '') {
                Config::set('mail.from.address', $fromAddress);
            }

            if ($fromName !== '') {
                Config::set('mail.from.name', $fromName);
            }
        } catch (\Throwable $e) {
        }
    }
}

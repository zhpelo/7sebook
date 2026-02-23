<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $table = 'app_settings';

    protected $fillable = [
        'setting_key',
        'setting_value',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $setting = static::query()->where('setting_key', $key)->first();

        return $setting?->setting_value ?? $default;
    }

    public static function getValues(array $defaults): array
    {
        $keys = array_keys($defaults);

        $stored = static::query()
            ->whereIn('setting_key', $keys)
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        $result = [];
        foreach ($defaults as $key => $defaultValue) {
            $result[$key] = array_key_exists($key, $stored) ? $stored[$key] : $defaultValue;
        }

        return $result;
    }

    public static function setValues(array $settings): void
    {
        foreach ($settings as $key => $value) {
            static::query()->updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => (string) $value]
            );
        }
    }
}

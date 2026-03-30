<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ChatbotBotSetting extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'chatbot_bot_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    /* ── Static Accessors ─────────────────── */

    /**
     * Get a bot setting value by key, with Redis caching.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $cacheKey = config('chatbot.cache_prefix') . 'setting:' . $key;
        $ttl = config('chatbot.cache_ttl', 3600);

        return Cache::remember($cacheKey, $ttl, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            return match ($setting->type) {
                'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
                'integer' => (int) $setting->value,
                'json'    => json_decode($setting->value, true),
                default   => $setting->value,
            };
        });
    }

    /**
     * Set a bot setting value by key, and clear cache.
     */
    public static function setValue(string $key, mixed $value, string $type = 'string', string $description = null): void
    {
        $storeValue = is_array($value) ? json_encode($value) : (string) $value;

        static::updateOrCreate(
            ['key' => $key],
            [
                'value'       => $storeValue,
                'type'        => $type,
                'description' => $description,
            ]
        );

        $cacheKey = config('chatbot.cache_prefix') . 'setting:' . $key;
        Cache::forget($cacheKey);
    }

    /**
     * Clear all setting caches.
     */
    public static function clearCache(): void
    {
        $settings = static::all();
        foreach ($settings as $setting) {
            $cacheKey = config('chatbot.cache_prefix') . 'setting:' . $setting->key;
            Cache::forget($cacheKey);
        }
    }
}

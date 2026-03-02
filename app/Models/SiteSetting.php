<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_active',
        'validation_rules',
        'options',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'validation_rules' => 'array',
        'options' => 'array'
    ];

    /**
     * Get setting value by key
     */
    public static function get($key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->where('is_active', true)->first();
            
            if (!$setting) {
                return $default;
            }

            return self::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set setting value
     */
    public static function set($key, $value, $type = 'text')
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'label' => ucwords(str_replace('_', ' ', $key)),
                'group' => 'general'
            ]
        );

        Cache::forget("setting_{$key}");
        Cache::forget('all_settings');
        
        return $setting;
    }

    /**
     * Get all settings grouped
     */
    public static function getAllGrouped()
    {
        return Cache::remember('all_settings', 3600, function () {
            return self::where('is_active', true)
                ->orderBy('group')
                ->orderBy('sort_order')
                ->orderBy('label')
                ->get()
                ->groupBy('group');
        });
    }

    /**
     * Cast value to appropriate type
     */
    protected static function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'number':
                return is_numeric($value) ? (float) $value : 0;
            case 'json':
                return json_decode($value, true) ?: [];
            default:
                return $value;
        }
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache()
    {
        Cache::forget('all_settings');
        
        // Clear individual setting caches
        self::all()->each(function ($setting) {
            Cache::forget("setting_{$setting->key}");
        });
    }

    /**
     * Get settings by group
     */
    public static function getByGroup($group)
    {
        return self::where('group', $group)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    /**
     * Get form input type based on setting type
     */
    public function getInputType()
    {
        switch ($this->type) {
            case 'boolean':
                return 'checkbox';
            case 'number':
                return 'number';
            case 'file':
                return 'file';
            case 'textarea':
                return 'textarea';
            case 'json':
                return 'textarea';
            default:
                return 'text';
        }
    }

    /**
     * Get formatted value for display
     */
    public function getDisplayValue()
    {
        switch ($this->type) {
            case 'boolean':
                return $this->value ? 'Yes' : 'No';
            case 'file':
                return $this->value ? basename($this->value) : 'No file';
            case 'json':
                return json_encode(json_decode($this->value), JSON_PRETTY_PRINT);
            default:
                return $this->value;
        }
    }
} 
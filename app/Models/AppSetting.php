<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $table = 'app_settings';

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        $val = $setting->value;
        $decoded = json_decode($val, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $val;
    }

    public static function set(string $key, $value, ?string $description = null): void
    {
        $val = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $val, 'description' => $description]
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [ 'key', 'value' ];

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings.all'));
        static::deleted(fn () => Cache::forget('settings.all'));
    }

    public static function allValues(): array
    {
        return Cache::rememberForever('settings.all', function () {
            return static::query()->pluck('value', 'key')->toArray();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        // Deliberately NOT data_get(): our keys literally contain dots
        // ("shipping.inside_dhaka"), and data_get would read them as a nested
        // path and silently fall through to the default every single time.
        return static::allValues()[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /**
     * Resolve a setting value, preferring the seeded database row and
     * falling back to `config('salon.*')` (itself env-driven) when absent.
     */
    public static function resolve(string $key, mixed $default = null): mixed
    {
        $stored = static::query()->where('key', $key)->value('value');

        return $stored !== null ? $stored : config("salon.{$key}", $default);
    }

    public static function resolveBool(string $key, bool $default = false): bool
    {
        $value = static::resolve($key, $default);

        return is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

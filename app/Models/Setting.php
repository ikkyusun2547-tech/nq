<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tiny key-value store for the handful of values an admin should be able to
 * tune without a code deploy (currently just graduation criteria — see
 * ActivityEvaluationService). No caching layer: this is an admin-panel-scale
 * table read a few times per request, not a hot path.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getJson(string $key, mixed $default = null): mixed
    {
        $raw = static::where('key', $key)->value('value');

        if ($raw === null) {
            return $default;
        }

        return json_decode($raw, true) ?? $default;
    }

    public static function setJson(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
    }
}

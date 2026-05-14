<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    // Récupère une valeur (avec valeur par défaut)
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    // Sauvegarde ou met à jour une valeur
    public static function set(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    // Récupère tous les settings en tableau key => value
    public static function getAllAsArray(): array
    {
        return static::all()->pluck('value', 'key')->toArray();
    }
}
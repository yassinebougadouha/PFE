<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'section', 'is_secret'];

    protected $casts = [
        'is_secret' => 'boolean',
    ];

    // Récupère une valeur (avec valeur par défaut)
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    // Sauvegarde ou met à jour une valeur
    public static function set(string $key, $value, ?string $section = null, bool $isSecret = false): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'section' => $section, 'is_secret' => $isSecret]
        );
    }

    // Récupère tous les settings en tableau key => value
    public static function getAllAsArray(): array
    {
        return static::all()->pluck('value', 'key')->toArray();
    }

    // Récupère les settings par section
    public static function getBySection(string $section): array
    {
        return static::where('section', $section)->pluck('value', 'key')->toArray();
    }
}
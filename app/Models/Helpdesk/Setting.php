<?php

namespace App\Models\Helpdesk;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $table = 'ticketit_settings';

    protected $fillable = ['lang', 'slug', 'value', 'default'];

    /**
     * Scope: filter by slug.
     */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function bySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * Grab a setting from cached Settings table by slug.
     * Cache lifetime: 60 minutes.
     */
    public static function grab(string $slug): mixed
    {
        $settings = Cache::remember('settings', now()->addMinutes(60), self::all(...));

        $setting = $settings->firstWhere('slug', $slug);

        if (!$setting) {
            return null;
        }

        if ($setting->lang) {
            return trans($setting->lang);
        }

        return self::isSerialized($setting->value)
            ? unserialize($setting->value)
            : $setting->value;
    }

    /**
     * Check if a parameter under Value or Default columns is serialized.
     */
    public static function isSerialized(mixed $data, bool $strict = true): bool
    {
        if (!is_string($data)) {
            return false;
        }

        $data = trim($data);

        if ($data === 'N;') {
            return true;
        }

        if (strlen($data) < 4 || $data[1] !== ':') {
            return false;
        }

        if ($strict) {
            $lastc = substr($data, -1);
            if ($lastc !== ';' && $lastc !== '}') {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace = strpos($data, '}');
            if ($semicolon === false && $brace === false) {
                return false;
            }
            if ($semicolon !== false && $semicolon < 3) {
                return false;
            }
            if ($brace !== false && $brace < 4) {
                return false;
            }
        }

        $token = $data[0];
        return match ($token) {
            's' => $strict
                ? substr($data, -2, 1) === '"'
                : str_contains($data, '"'),
            'a', 'O' => (bool) preg_match("/^{$token}:[0-9]+:/s", $data),
            'b', 'i', 'd' => (bool) preg_match("/^{$token}:[0-9.E-]+;$/", $data),
            default => false,
        };
    }
}

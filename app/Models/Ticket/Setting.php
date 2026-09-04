<?php

namespace App\Models\Ticket;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Setting extends Model
{
    protected $table = 'ticketit_settings';

    protected $fillable = ['lang', 'slug', 'value', 'default'];

    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    public static function grab(string $slug): mixed
    {
        $settings = Cache::remember('ticketit_settings', 3600, fn() => self::all());
        $setting = $settings->where('slug', $slug)->first();
        
        $result = null;

        if ($setting) {
            $result = $setting->lang
                ? trans($setting->lang)
                : self::resolveValue($setting->value);
        }

        return $result;
    }

    private static function resolveValue(mixed $value): mixed
    {
        $resolved = $value;

        if (self::isSerialized($value)) {
            try {
                $resolved = unserialize((string) $value);
            } catch (\Exception $e) {
                Log::error("Erro de desserialização: " . $e->getMessage());
            }
        }

        return $resolved;
    }

    public static function isSerialized(mixed $data): bool
    {
        if (!is_string($data)) {
            return false;
        }

        $trimmed = trim($data);
        
        if ($trimmed === 'N;') {
            return true;
        }

        if (strlen($trimmed) < 4 || $trimmed[1] !== ':') {
            return false;
        }

        $token = $trimmed[0];
        $lastChar = substr($trimmed, -1);

        return match ($token) {
            's' => $lastChar === ';' && substr($trimmed, -2, 1) === '"',
            'a', 'O' => (bool) preg_match("/^{$token}:[0-9]+:/s", $trimmed),
            'b', 'i', 'd' => (bool) preg_match("/^{$token}:[0-9.E-]+;$/", $trimmed),
            default => false,
        };
    }
}

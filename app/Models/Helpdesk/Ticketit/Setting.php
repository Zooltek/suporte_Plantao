<?php

namespace App\Models\Helpdesk\Ticketit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Setting extends Model
{
    /** @var array<int, string> */
    protected $fillable = ['lang', 'slug', 'value', 'default'];

    /** @var string */
    protected $table = 'ticketit_settings';

    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    /**
     * S1142: Apenas dois pontos de saída claros.
     */
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
        $isValid = false;

        if (is_string($data)) {
            $trimmed = trim($data);
            $isValid = self::checkSerializationSyntax($trimmed);
        }

        return $isValid;
    }

    /**
     * S3776 & S1142: Redução de complexidade e retorno único.
     * S6600: Removido parênteses desnecessários em retornos e condições.
     */
    private static function checkSerializationSyntax(string $data): bool
    {
        if ($data === 'N;') {
            return true;
        }

        if (strlen($data) < 4 || $data[1] !== ':') {
            return false;
        }

        $token = $data[0];
        $lastChar = substr($data, -1);
        $isValid = false;

        switch ($token) {
            case 's':
                $isValid = $lastChar === ';' && substr($data, -2, 1) === '"';
                break;
            case 'a':
            case 'O':
                $isValid = (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
                break;
            case 'b':
            case 'i':
            case 'd':
                $isValid = (bool) preg_match("/^{$token}:[0-9.E-]+;$/", $data);
                break;
            default:
                $isValid = false;
                break;
        }

        return $isValid;
    }
}

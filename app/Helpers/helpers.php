<?php

use Illuminate\Support\Str;

if (!function_exists('titleCase')) {
    function titleCase(?string $value): string
    {
        return Str::title($value ?? '');
    }
}

if (!function_exists('telephone')) {
    function telephone(?string $value): string
    {
        if (!$value) return '';
        // Exemplo simples: (xx) xxxx-xxxx
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $value);
    }
}

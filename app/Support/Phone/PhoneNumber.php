<?php

namespace App\Support\Phone;

final class PhoneNumber
{
    /**
     * Normaliza um telefone mantendo apenas dígitos.
     */
    public static function normalize(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    /**
     * Gera variações comuns do mesmo número para comparação.
     *
     * Exemplos:
     * - 5527999990000 -> 5527999990000, 27999990000, 2799990000
     * - (27) 99999-0000 -> 27999990000, 5527999990000, 2799990000
     */
    public static function variants(?string $value): array
    {
        $digits = self::normalize($value);

        if ($digits === '') {
            return [];
        }

        $variants = [$digits];

        if (str_starts_with($digits, '55') && strlen($digits) > 11) {
            $national = substr($digits, 2);
            $variants[] = $national;

            if (strlen($national) === 11) {
                $withoutNinthDigit = substr($national, 0, 2) . substr($national, 3);
                $variants[] = $withoutNinthDigit;
                $variants[] = '55' . $withoutNinthDigit;
            }
        } elseif (strlen($digits) === 11) {
            $withoutNinthDigit = substr($digits, 0, 2) . substr($digits, 3);

            $variants[] = $withoutNinthDigit;
            $variants[] = '55' . $digits;
            $variants[] = '55' . $withoutNinthDigit;
        } elseif (strlen($digits) === 10) {
            $variants[] = '55' . $digits;
        }

        return array_values(array_unique(array_filter($variants)));
    }
}

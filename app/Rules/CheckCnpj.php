<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CheckCnpj implements ValidationRule
{
    /**
     * Executa a validação do CNPJ.
     * * @param string $attribute
     * @param mixed $value
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // php:S6353 - Uso conciso de \D
        $cnpj = preg_replace('/\D/', '', (string) $value);

        // Corrigido: preg_match PRECISA de 2 argumentos (pattern e subject)
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            $fail('O campo :attribute não é um CNPJ válido.');
            return;
        }

        if (!$this->isCnpjValid($cnpj)) {
            $fail('O campo :attribute é inválido.');
        }
    }

    /**
     * Validação matemática refatorada para evitar alteração de contadores
     * de loop dentro do corpo (Sonar Clean Code).
     */
    private function isCnpjValid(string $cnpj): bool
    {
        for ($t = 12; $t < 14; $t++) {
            $sum = 0;
            // Define o peso inicial baseado no dígito que está sendo validado
            $weight = ($t === 12) ? 5 : 6;

            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cnpj[$i] * $weight;
                
                // Em vez de $weight--, usamos uma lógica que não altera o contador
                // do loop principal e segue as regras do CNPJ (após 2 volta para 9)
                $weight = ($weight === 2) ? 9 : $weight - 1;
            }

            $digit = ((10 * $sum) % 11) % 10;

            if ((int) $cnpj[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }
}

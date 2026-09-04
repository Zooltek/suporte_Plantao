<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CheckCep implements ValidationRule
{
    /**
     * @param string $attribute  (O nome do campo, ex: zipcode)
     * @param mixed $value       (O valor vindo do formulário)
     * @param \Closure $fail     (O callback de falha)
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cep = preg_replace('/\D/', '', (string) $value);

        if (strlen($cep) !== 8 || preg_match('/^(\d)\1{7}$/', $cep)) {
            $fail('O campo :attribute deve ser um CEP válido com 8 dígitos.');
        }
    }
}

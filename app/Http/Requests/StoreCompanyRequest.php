<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:20'],
            'city_registration' => ['nullable', 'string', 'regex:/^\d{7}$/'],
            'state_registration' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'telephone_2' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'observations' => ['nullable', 'string'],
            'module_ids' => ['nullable', 'array'],
            'module_ids.*' => ['integer', 'exists:company_module_types,id'],
            'contacts' => ['nullable', 'array'],
            'contacts.*' => ['array:name,phone,email,is_main'],
            'contacts.*.name' => ['nullable', 'string', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:30'],
            'contacts.*.email' => ['nullable', 'string', 'email', 'max:255'],
            'contacts.*.is_main' => ['nullable'],
        ];
    }
}

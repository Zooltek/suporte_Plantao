<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreElementTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'module_id' => ['required', 'exists:modules,id'],
            'group_id'  => ['required', 'exists:element_groups,id'],
        ];
    }
}
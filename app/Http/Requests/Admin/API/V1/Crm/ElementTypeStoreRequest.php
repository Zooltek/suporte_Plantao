<?php

namespace App\Http\Requests\Admin\API\V1\Crm;

use Illuminate\Foundation\Http\FormRequest;

class ElementTypeStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'form_id'    => ['required', 'integer'],
            'name'       => ['required', 'string', 'max:100'],
            'label'      => ['required', 'string', 'max:255'],
            'type'       => ['required', 'string', 'in:text,textarea,number,select,checkbox,radio,email,phone,date'],
            'data'       => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => str($this->name)->slug('_')->toString(),
            ]);
        }
    }
}

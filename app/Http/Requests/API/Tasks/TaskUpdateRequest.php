<?php

namespace App\Http\Requests\API\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class TaskUpdateRequest extends FormRequest
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
            'title'       => ['sometimes', 'string', 'max:255'],
            'content'     => ['sometimes', 'string'],
            'user_id'     => ['sometimes', 'integer', 'exists:users,id'],
            'status_id'   => ['sometimes', 'integer'],
            'delivery_at' => ['nullable', 'date_format:d/m/Y'],
            'priority_id' => ['sometimes', 'integer', 'between:1,3'],
        ];
    }

    /**
     * Customização das mensagens de erro.
     */
    public function attributes(): array
    {
        return [
            'delivery_at' => 'data de entrega',
            'user_id'     => 'responsável',
            'title'       => 'título',
        ];
    }
}

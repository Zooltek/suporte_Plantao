<?php

namespace App\Http\Requests\API\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class NotificationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ref_id'    => ['required', 'integer'],
            'content'   => ['required', 'string', 'max:1000'],
            'kind'      => ['required', 'string'],
            'author_id' => ['required', 'integer', 'exists:users,id'],
            'user_id'   => ['required', 'integer', 'exists:users,id'],
            'status'    => ['nullable', 'string', 'in:don,can,rej,pen'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'O status fornecido é inválido.',
            'exists'    => 'O usuário informado não existe em nossa base.',
        ];
    }
}

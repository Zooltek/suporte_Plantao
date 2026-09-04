<?php

declare(strict_types=1);

namespace App\Http\Requests\API\Tasks;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CommentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => [
                'required',
                'string',
                'min:1',
                'max:5000'
            ],
            'task_id' => [
                'required',
                'integer',
                'exists:tasks,id'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'O conteúdo do comentário não pode estar vazio.',
            'task_id.exists'   => 'A tarefa informada é inválida ou não existe.',
        ];
    }
}

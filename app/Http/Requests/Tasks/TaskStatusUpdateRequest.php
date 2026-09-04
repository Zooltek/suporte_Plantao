<?php

namespace App\Http\Requests\Tasks;

use App\Services\Access\AccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskStatusUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('admin') ?? $this->user();

        return app(AccessService::class)->hasStaffAccess($user);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in([
                'pen',
                'pro',
                'sto',
                'don',
                'tdo',
                'can',
            ])],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Selecione o novo status da tarefa.',
            'status.in' => 'Escolha um status válido para continuar.',
        ];
    }
}

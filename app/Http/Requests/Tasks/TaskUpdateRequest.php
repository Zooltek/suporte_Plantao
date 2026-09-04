<?php

namespace App\Http\Requests\Tasks;

use App\Models\Tasks\Task;
use App\Services\Access\AccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskUpdateRequest extends FormRequest
{
    protected $errorBag = 'taskUpdate';

    public function authorize(): bool
    {
        $user = $this->user('admin') ?? $this->user();

        return app(AccessService::class)->hasStaffAccess($user);
    }

    protected function prepareForValidation(): void
    {
        $delivery = $this->input('delivery_at');

        if ($delivery && preg_match('/^\d{4}-\d{2}-\d{2}$/', $delivery)) {
            [$y, $m, $d] = explode('-', $delivery);

            $this->merge([
                'delivery_at' => "{$d}/{$m}/{$y}",
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'classification' => ['nullable', 'string', Rule::in(array_keys(Task::CLASSIFICATIONS))],
            'delivery_at' => ['nullable', 'date_format:d/m/Y'],
            'status' => ['required', 'string', Rule::in([
                'pen',
                'pro',
                'sto',
                'don',
                'tdo',
                'can',
            ])],
            'editing_task_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Informe o título da tarefa.',
            'content.required' => 'Descreva o que precisa ser feito.',
            'user_id.required' => 'Selecione o responsável pela tarefa.',
            'status.required' => 'Selecione o status da tarefa.',
            'status.in' => 'Escolha um status válido para a tarefa.',
        ];
    }
}

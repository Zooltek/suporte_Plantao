<?php

namespace App\Http\Requests\Tasks;

use App\Models\Tasks\Task;
use App\Services\Access\AccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskInboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('admin') ?? $this->user();

        return app(AccessService::class)->hasStaffAccess($user);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['open', 'done', 'all'])],
            'task_id' => ['nullable', 'integer', 'min:1'],
            'classification' => ['nullable', Rule::in(array_keys(Task::CLASSIFICATIONS))],
            'customer_id' => ['nullable', 'integer', 'min:1', 'exists:customers,id'],
            'project_id' => ['nullable', 'integer', 'min:1', 'exists:tasks_projects,id'],
        ];
    }
}

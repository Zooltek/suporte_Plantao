<?php

namespace App\Http\Requests\API\Tasks;

use App\Models\Tasks\Task;
use App\Services\Access\AccessService;
use App\Services\Admin\Tasks\TaskModuleCatalogService;
use App\Services\Admin\Tasks\TaskRatContextService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class TaskStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user('admin') ?? $this->user();

        return app(AccessService::class)->hasStaffAccess($user);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'author_id' => ['required', 'integer', 'exists:users,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('tasks_projects', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'software_id' => ['nullable', 'integer', 'exists:softwares,id'],
            'module_label_id' => [
                'nullable',
                'integer',
                Rule::exists('labels', 'id')->where(fn ($query) => $query
                    ->whereNull('parent_id')
                    ->where('is_active', true)),
            ],
            'submodule_label_id' => [
                'nullable',
                'integer',
                Rule::exists('labels', 'id')->where(fn ($query) => $query
                    ->whereNotNull('parent_id')
                    ->where('is_active', true)),
            ],
            'rat_record_ids' => ['nullable', 'array'],
            'rat_record_ids.*' => ['integer', 'exists:schedule_record,id'],
            'labels' => ['nullable', 'array'],
            'labels.*' => ['integer', 'exists:labels,id'],
            'delivery_at' => ['nullable', 'date_format:d/m/Y'],
            'classification' => ['nullable', 'string', 'in:'.implode(',', array_keys(Task::CLASSIFICATIONS))],
            'file' => [
                'nullable',
                File::types(['jpg', 'jpeg', 'png', 'bmp', 'gif', 'pdf', 'docx', 'xlsx', 'zip', 'rar', 'txt', 'mp4'])
                    ->max(51200), // 50 MB — alinhado com TaskUploadRequest
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];
        $authUserId = auth('admin')->id() ?? auth()->id();

        if (! $this->has('author_id')) {
            $data['author_id'] = $authUserId;
        }

        // Normaliza delivery_at: input[type=date] envia Y-m-d, API envia d/m/Y
        $delivery = $this->input('delivery_at');
        if ($delivery && preg_match('/^\d{4}-\d{2}-\d{2}$/', $delivery)) {
            [$y, $m, $d] = explode('-', $delivery);
            $data['delivery_at'] = "{$d}/{$m}/{$y}";
        }

        if (! $this->has('labels') && ($this->filled('module_label_id') || $this->filled('submodule_label_id'))) {
            $data['labels'] = array_values(array_filter([
                $this->filled('submodule_label_id')
                    ? (int) $this->input('submodule_label_id')
                    : ($this->filled('module_label_id') ? (int) $this->input('module_label_id') : null),
            ]));
        }

        if ($data) {
            $this->merge($data);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $customerId = $this->filled('customer_id') ? (int) $this->input('customer_id') : null;
            $projectId = $this->filled('project_id') ? (int) $this->input('project_id') : null;
            $moduleId = $this->filled('module_label_id') ? (int) $this->input('module_label_id') : null;
            $submoduleId = $this->filled('submodule_label_id') ? (int) $this->input('submodule_label_id') : null;
            $ratRecordIds = collect($this->input('rat_record_ids', []))
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();

            if (($moduleId || $submoduleId) && ! $projectId) {
                $validator->errors()->add('project_id', 'Selecione um projeto antes de classificar por módulo.');
            }

            if ($submoduleId && ! $moduleId) {
                $validator->errors()->add('module_label_id', 'Selecione um módulo antes do submódulo.');
            }

            if ($projectId && $customerId && ! $this->taskModuleCatalog()->projectExists($projectId, $customerId)) {
                $validator->errors()->add('project_id', 'O projeto selecionado não está disponível para o cliente informado.');
            }

            if ($ratRecordIds !== [] && ! $customerId) {
                $validator->errors()->add('customer_id', 'Selecione um cliente antes de relacionar RATs à tarefa.');
            }

            if ($ratRecordIds !== [] && $customerId && ! $this->taskRatContext()->recordsBelongToCustomer($ratRecordIds, $customerId)) {
                $validator->errors()->add('rat_record_ids', 'Os RATs selecionados não pertencem ao cliente informado.');
            }

            if ($projectId && $ratRecordIds !== []) {
                $projectName = $this->taskRatContext()->resolveProjectName($projectId);

                if ($projectName && ! $this->taskRatContext()->recordsBelongToProject($ratRecordIds, $projectName)) {
                    $validator->errors()->add('rat_record_ids', 'Os RATs selecionados não pertencem ao projeto informado.');
                }
            }

            if (! $projectId || ! $moduleId) {
                return;
            }

            if (! $this->taskModuleCatalog()->projectAllowsModule($projectId, $moduleId, $customerId)) {
                $validator->errors()->add('module_label_id', 'O módulo selecionado não está disponível para o projeto informado.');
            }

            if ($ratRecordIds !== []) {
                $scheduleModuleId = $this->taskRatContext()->resolveScheduleModuleIdForLabel($moduleId);

                if ($scheduleModuleId && ! $this->taskRatContext()->recordsBelongToScheduleModule($ratRecordIds, $scheduleModuleId)) {
                    $validator->errors()->add('rat_record_ids', 'Os RATs selecionados não correspondem ao módulo técnico informado.');
                }
            }

            if (! $submoduleId) {
                return;
            }

            if (! $this->taskModuleCatalog()->submoduleBelongsToModule($moduleId, $submoduleId)) {
                $validator->errors()->add('submodule_label_id', 'O submódulo selecionado não pertence ao módulo informado.');
            }
        });
    }

    private function taskModuleCatalog(): TaskModuleCatalogService
    {
        return app(TaskModuleCatalogService::class);
    }

    private function taskRatContext(): TaskRatContextService
    {
        return app(TaskRatContextService::class);
    }
}

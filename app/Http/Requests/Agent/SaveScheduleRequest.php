<?php

declare(strict_types=1);

namespace App\Http\Requests\Agent;

use App\Contracts\Repositories\ScheduleTypeRepositoryInterface;
use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $agentId = $this->input('agent_id');

        $this->merge([
            'agent_id'    => blank($agentId) || (int) $agentId === 0 ? Auth::guard('admin')->id() : $agentId,
            'customer_id' => $this->filled('customer_id') ? $this->integer('customer_id') : null,
            'module_id'   => $this->filled('module_id') ? $this->integer('module_id') : null,
            'ticket_id'   => $this->filled('ticket_id') ? $this->integer('ticket_id') : null,
            'kind'        => $this->filled('ticket_id')
                ? Schedule::KIND_TICKET
                : ($this->input('kind') ?: Schedule::KIND_CLIENT),
            'title'       => trim((string) $this->input('title', '')),
            'contact'     => trim((string) $this->input('contact', '')) ?: null,
            'obs'         => trim((string) $this->input('obs', '')) ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $activeSlugs = $this->scheduleTypes()
            ->getActive()
            ->pluck('slug')
            ->all();

        return [
            'date'        => ['required', 'date'],
            'start_hour'  => ['required', 'date_format:H:i'],
            'kind'        => ['required', Rule::in($activeSlugs)],
            'title'       => ['required', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'agent_id'    => ['required', 'exists:users,id'],
            'module_id'   => ['nullable', 'exists:schedule_record_module,id'],
            'contact'     => ['nullable', 'string', 'max:255'],
            'obs'         => ['nullable', 'string'],
            'ticket_id'   => ['nullable', 'exists:ticketit,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $kind = (string) $this->input('kind');

            if ($kind === Schedule::KIND_TICKET && !$this->filled('ticket_id')) {
                $validator->errors()->add('ticket_id', 'Informe o ticket de origem do agendamento.');

                return;
            }

            $type = $this->scheduleTypes()->findBySlug($kind);

            if ($type && $type->requires_customer && !$this->filled('customer_id') && !$this->filled('ticket_id')) {
                $validator->errors()->add(
                    'customer_id',
                    "Selecione o cliente para o tipo \"{$type->label}\"."
                );
            }
        });
    }

    private function scheduleTypes(): ScheduleTypeRepositoryInterface
    {
        return app(ScheduleTypeRepositoryInterface::class);
    }
}

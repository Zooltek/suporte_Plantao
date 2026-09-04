<?php

namespace App\Http\Requests\Agent;

use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Suporta tanto route model binding (objeto Schedule) quanto ID numérico
        $param = $this->route('schedule');

        $schedule = $param instanceof Schedule
            ? $param
            : Schedule::find((int) $param);

        return $schedule && !$schedule->isFinalized();
    }

    public function rules(): array
    {
        return [
            'date'           => ['required', 'date'],
            'start_hour'     => ['required', 'date_format:H:i'],
            'end_hour'       => ['required', 'date_format:H:i', 'after:start_hour'],
            'interval_start' => ['nullable', 'date_format:H:i'],
            'interval_end'   => ['nullable', 'date_format:H:i', 'after:interval_start'],
            'agent_id'       => ['required', 'exists:users,id'],
            'module_id'      => ['required', 'exists:schedule_record_module,id'],
            'customer_id'    => ['nullable', 'exists:customers,id'],
            'contact'        => ['nullable', 'string', 'max:255'],
            'obs'            => ['nullable', 'string'],
            'version'        => ['nullable', 'string', 'max:50'],
            'release'        => ['nullable', 'string', 'max:50'],
            'resolved'       => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_hour.after' => 'A hora final deve ser posterior à hora inicial.',
        ];
    }
}

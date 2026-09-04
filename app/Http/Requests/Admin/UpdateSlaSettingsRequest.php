<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSlaSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [];

        foreach ($this->fields() as $field) {
            $rules[$field] = ['required', 'integer', 'min:1'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'sla_high_attention' => 'SLA Alta - Atenção',
            'sla_high_warning' => 'SLA Alta - Aviso',
            'sla_high_critical' => 'SLA Alta - Crítico',
            'sla_med_attention' => 'SLA Média - Atenção',
            'sla_med_warning' => 'SLA Média - Aviso',
            'sla_med_critical' => 'SLA Média - Crítico',
            'sla_low_attention' => 'SLA Baixa - Atenção',
            'sla_low_warning' => 'SLA Baixa - Aviso',
            'sla_low_critical' => 'SLA Baixa - Crítico',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function fields(): array
    {
        return [
            'sla_high_attention',
            'sla_high_warning',
            'sla_high_critical',
            'sla_med_attention',
            'sla_med_warning',
            'sla_med_critical',
            'sla_low_attention',
            'sla_low_warning',
            'sla_low_critical',
        ];
    }
}

<?php

namespace App\Http\Requests\Admin\Reports;

use App\Enums\Reports\ImplementationClientSituation;
use App\Enums\Reports\ReportPeriodPreset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ReportPeriodFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_preset' => ['nullable', Rule::in(ReportPeriodPreset::values())],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'software_id' => ['nullable', 'integer', 'exists:softwares,id'],
            'implementation_status' => ['nullable', Rule::in(ImplementationClientSituation::values())],
        ];
    }

    public function periodStart(?Carbon $fallback = null): ?Carbon
    {
        if ($this->filled('date_from')) {
            return $this->date('date_from')->startOfDay();
        }

        return $fallback?->copy()->startOfDay();
    }

    public function periodEnd(?Carbon $fallback = null): ?Carbon
    {
        if ($this->filled('date_to')) {
            return $this->date('date_to')->endOfDay();
        }

        return $fallback?->copy()->endOfDay();
    }

    public function hasCustomPeriod(): bool
    {
        return $this->filled('date_from') || $this->filled('date_to');
    }

    public function selectedPreset(ReportPeriodPreset $default): ReportPeriodPreset
    {
        if ($this->hasCustomPeriod()) {
            return ReportPeriodPreset::CUSTOM;
        }

        $value = $this->input('period_preset');

        if (! is_string($value) || $value === '' || $value === ReportPeriodPreset::CUSTOM->value) {
            return $default;
        }

        return ReportPeriodPreset::from($value);
    }

    public function hasActivePeriodFilter(ReportPeriodPreset $default): bool
    {
        if ($this->hasCustomPeriod()) {
            return true;
        }

        $value = $this->input('period_preset');

        if (! is_string($value) || $value === '' || $value === ReportPeriodPreset::CUSTOM->value) {
            return false;
        }

        return $value !== $default->value;
    }

    public function softwareId(): ?int
    {
        return $this->filled('software_id')
            ? $this->integer('software_id')
            : null;
    }

    public function implementationStatus(): ImplementationClientSituation
    {
        $value = $this->input('implementation_status');

        if (! is_string($value) || $value === '') {
            return ImplementationClientSituation::ALL;
        }

        return ImplementationClientSituation::from($value);
    }

    public function inputDate(string $field, ?Carbon $fallback = null): string
    {
        if ($this->filled($field)) {
            return (string) $this->input($field);
        }

        return $fallback?->format('Y-m-d') ?? '';
    }
}

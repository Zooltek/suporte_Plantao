<?php

namespace App\Http\Requests\Agent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class MonitorIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'compact' => ['nullable', 'boolean'],
            'start'   => ['nullable', 'date_format:d/m/Y'],
            'end'     => ['nullable', 'date_format:d/m/Y', 'after_or_equal:start'],
        ];
    }

    /**
     * Retorna as datas já parseadas em Carbon
     */
    public function getDateRange(): array
    {
        $now = now();
        
        if ($this->filled(['start', 'end'])) {
            return [
                Carbon::createFromFormat('d/m/Y', $this->start)->startOfDay(),
                Carbon::createFromFormat('d/m/Y', $this->end)->endOfDay(),
            ];
        }

        return [
            $now->copy()->startOfDay(),
            $now->copy()->endOfDay()
        ];
    }
}
<?php

namespace App\Http\Requests\Agent\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class TicketIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'integer', 'exists:ticketit_statuses,id'],
            'category' => ['nullable', 'integer', 'exists:solutions_category,category_id'],
            'company' => ['nullable', 'integer', 'exists:customers,id'],
            'agent' => ['nullable', 'string'],
            'origin' => ['nullable', 'integer', 'exists:ticketit_origin,id'],
            'department' => ['nullable', 'integer', 'exists:user_department,id'],
            'order' => ['nullable', 'in:1,2,3'],
            'mine' => ['nullable', 'boolean'],
            'unassigned' => ['nullable', 'boolean'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ];
    }
}

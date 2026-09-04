<?php

namespace App\Http\Requests\API\V1\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class TicketIssueStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

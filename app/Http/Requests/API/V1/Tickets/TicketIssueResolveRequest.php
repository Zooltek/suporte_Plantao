<?php

namespace App\Http\Requests\API\V1\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class TicketIssueResolveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'solution' => ['required', 'string', 'max:5000'],
        ];
    }
}

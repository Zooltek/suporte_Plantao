<?php

namespace App\Http\Requests\API\V1\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes'      => 'nullable|string',
            'return_zap' => 'sometimes|boolean',
            'return_tel' => 'sometimes|boolean',
            'return_cel' => 'sometimes|boolean',
            'return_user_id' => 'nullable|integer|exists:users,id',
            'return_at'      => 'nullable|date',
        ];
    }
}

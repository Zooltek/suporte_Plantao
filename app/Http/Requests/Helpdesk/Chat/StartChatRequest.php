<?php

namespace App\Http\Requests\Helpdesk\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StartChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'min:5', 'max:150'],
            'category_id' => ['nullable', 'integer', 'exists:solutions_category,category_id'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
        ];
    }
}

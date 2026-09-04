<?php

namespace App\Http\Requests\Helpdesk\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SendChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }
}

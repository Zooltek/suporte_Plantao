<?php

namespace App\Http\Requests\Agent\Knowledge;

use Illuminate\Foundation\Http\FormRequest;

class KnowledgeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'      => ['required', 'string', 'max:255'],
            'problem'    => ['required', 'string'],
            'solution'   => ['required', 'string'],
            'tags'       => ['nullable', 'string', 'max:500'],
            'visibility' => ['required', 'in:internal,public'],
            'ticket_id'  => ['nullable', 'integer', 'exists:ticketit,id'],
            'category_id'=> ['nullable', 'integer'],
        ];
    }
}

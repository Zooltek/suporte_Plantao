<?php

namespace App\Http\Requests\API\Tasks\Changelog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ChangelogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => [
                'required',
                'integer',
                'exists:projects,id'
            ],
        ];
    }
    
    public function messages(): array
    {
        return [
            'project_id.required' => 'O identificador do projeto é obrigatório.',
            'project_id.exists'   => 'O projeto selecionado não foi encontrado em nossa base.',
        ];
    }
}

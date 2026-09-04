<?php

namespace App\Http\Requests\Admin\Helpdesk;

use App\Models\Ticket\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

class SaveStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('admin')->check();
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:100'],
            'color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $name = trim((string) $this->input('name'));

            if ($name === '') {
                return;
            }

            $routeStatus = $this->route('status');
            $statusId = $routeStatus instanceof Status
                ? (int) $routeStatus->getKey()
                : (int) ($routeStatus ?? $this->route('id') ?? 0);

            $duplicateExists = Status::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                ->when($statusId > 0, fn ($query) => $query->whereKeyNot($statusId))
                ->exists();

            if ($duplicateExists) {
                $validator->errors()->add('name', 'Já existe um status com esse nome.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'O nome do status é obrigatório.',
            'name.max'       => 'O nome do status não pode ultrapassar 100 caracteres.',
            'color.required' => 'A cor é obrigatória.',
            'color.regex'    => 'A cor deve estar no formato hexadecimal (ex: #6366f1).',
        ];
    }
}

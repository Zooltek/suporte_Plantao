<?php

namespace App\Http\Requests\Agent\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateUserPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('admin')->check();
    }

    public function rules(): array
    {
        return [
            'deployment_admin'          => ['boolean'],
            'can_manage_implementation' => ['boolean'],
        ];
    }

    /**
     * Garante que checkboxes desmarcados se tornem false
     * e os marcados se tornem true antes da validação.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'deployment_admin'          => $this->boolean('deployment_admin'),
            'can_manage_implementation' => $this->boolean('can_manage_implementation'),
        ]);
    }
}

<?php

namespace App\Http\Requests\Tasks;

use App\Services\Access\AccessService;
use Illuminate\Foundation\Http\FormRequest;

class TaskCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('admin') ?? $this->user();

        return app(AccessService::class)->hasStaffAccess($user);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
        ];
    }
}

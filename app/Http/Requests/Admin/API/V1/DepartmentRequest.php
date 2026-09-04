<?php

namespace App\Http\Requests\Admin\API\V1;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DepartmentRequest',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name',        type: 'string', maxLength: 255, example: 'Suporte Técnico'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Equipe de suporte ao cliente'),
    ]
)]
class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Access\AccessService;

class PrepareSolutionUpdateRequest extends FormRequest
{
    /**
     * Determina se o usuário tem permissão para editar este artigo.
     */
    public function authorize(): bool
    {
        $accessService = app(AccessService::class);

        return $accessService->hasStaffAccess($this->user());
    }

    /**
     * Regras de validação para a atualização da solução.
     */
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'min:3', 'max:255'],
            'content'     => ['required', 'string', 'min:10'],
            'category_id' => ['required', 'integer', 'exists:categories,category_id'],
            'status'      => ['nullable', 'boolean'],
            'tags'        => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'A categoria selecionada é inválida ou foi removida.',
            'content.min'        => 'O conteúdo do artigo deve ter pelo menos 10 caracteres.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Access\AccessService;

class PrepareSolutionStoreRequest extends FormRequest
{
    /**
     * Determina se o usuário tem permissão para criar um artigo.
     * Geralmente, apenas Agentes ou Admins podem criar soluções na KB.
     */
    public function authorize(): bool
    {
        $accessService = app(AccessService::class);

        return $accessService->hasStaffAccess($this->user());
    }

    /**
     * Regras de validação para a nova solução.
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
    
    public function attributes(): array
    {
        return [
            'title'       => 'Título do Artigo',
            'content'     => 'Conteúdo da Solução',
            'category_id' => 'Categoria',
        ];
    }
}

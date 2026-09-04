<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class FeedbackStoreRequest extends FormRequest
{
    /**
     * Determine se o usuário está autorizado a fazer esta requisição.
     * No Laravel 12, o retorno deve ser tipado como bool.
     */
    public function authorize(): bool
    {
        // Mantendo true para permitir o processamento, 
        // a menos que você implemente lógica de permissão aqui.
        return true;
    }

    /**
     * Regras de validação atualizadas.
     * Implementamos tipagem de retorno e regras mais seguras para o banco de dados.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            
            // adicionado 'string' para garantir o tipo de dado e 'max' para segurança de buffer
            'contact'     => ['required', 'string', 'max:255'],
            
            'form_id'      => ['required', 'exists:crm_feedback_forms,id'],
            
            'start'        => ['required_without:feedback_id', 'date_format:d/m/Y'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
            'contact'     => 'contato',
            'form_id'     => 'formulário',
            'start'       => 'data de início',
        ];
    }
}

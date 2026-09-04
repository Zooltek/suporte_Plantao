<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class FeedbackCancelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação atualizadas.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cancel_content' => ['required', 'string', 'min:5', 'max:5000'],
            
            // valida se o cliente realmente existe antes de tentar processar no Controller
            'customer_id'    => ['required', 'exists:customers,id'],
            
            // campos adicionais que o Controller utiliza na lógica de cancelamento
            'form_id'        => ['required', 'exists:crm_feedback_forms,id'],
            'feedback_id'    => ['nullable', 'exists:crm_feedback,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'cancel_content' => 'motivo do cancelamento',
            'customer_id'    => 'cliente',
            'form_id'        => 'formulário',
        ];
    }
}

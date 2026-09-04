<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class NotificationSendRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     * No Laravel 12, usamos apenas admins para disparar notificações globais.
     */
    public function authorize(): bool
    {
        return Auth::check() && (Auth::user()->ticketit_admin == 1);
    }

    /**
     * Regras de validação para o envio de notificações.
     */
    public function rules(): array
    {
        return [
            'group'   => ['required', 'string'], // 'all', 'users', 'agents' ou ID numérico
            'content' => ['required', 'string', 'min:3', 'max:500'],
            'action'  => ['nullable', 'string', 'max:255'],
            'image'   => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Nomes amigáveis para os campos (opcional).
     */
    public function attributes(): array
    {
        return [
            'group'   => 'Destinatário/Grupo',
            'content' => 'Conteúdo da Mensagem',
            'action'  => 'URL de Ação',
        ];
    }
}

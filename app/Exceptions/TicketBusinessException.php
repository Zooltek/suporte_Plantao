<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

/**
 * TicketBusinessException
 * * Esta exceção é lançada quando uma regra de negócio específica dos tickets é violada.
 * Substitui o uso de Exception genérica (Sonar S112).
 */
class TicketBusinessException extends Exception
{
    /**
     * O código de status HTTP padrão para erros de regra de negócio
     * é o 422 (Unprocessable Entity).
     */
    protected $code = Response::HTTP_UNPROCESSABLE_ENTITY;

    public function report(): bool
    {
        // Retorne false se não quiser que o erro vá para o log padrão do Laravel
        return true;
    }

    /**
     * Renderiza a exceção em uma resposta HTTP.
     * O Laravel chama este método automaticamente se a exceção não for capturada.
     */
    public function render($request)
    {
        // Se a requisição for API ou AJAX, retorna JSON
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => $this->getMessage(),
            ], $this->code);
        }

        // Para requisições web normais, volta com erro na sessão
        return back()
            ->withInput()
            ->with('warning', $this->getMessage());
    }
}

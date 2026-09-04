<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TicketFinalizationException extends Exception
{
    /**
     * Mensagem padrão caso nenhuma seja passada no throw.
     */
    protected $message = 'O chamado não cumpre os requisitos para finalização.';

    /**
     * Código 422: Unprocessable Entity
     */
    protected $code = Response::HTTP_UNPROCESSABLE_ENTITY;

    /**
     * remove a lógica de "como exibir o erro" de dentro do Controller.
     */
    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'validation_error',
                'message' => $this->getMessage(),
            ], $this->code);
        }

        // volta para a página anterior com os dados digitados e a mensagem
        return back()
            ->withInput()
            ->with('warning', $this->getMessage());
    }
}

<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

/**
 * Exceção específica para erros de payload do chatbot WhatsApp.
 */
class WhatsAppPayloadException extends Exception
{
    protected $code = Response::HTTP_UNPROCESSABLE_ENTITY;

    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
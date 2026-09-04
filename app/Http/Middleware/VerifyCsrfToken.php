<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * Exceções CSRF gerenciadas centralmente em bootstrap/app.php
     * via $middleware->validateCsrfTokens(except: [...]).
     * Esta classe existe apenas para compatibilidade com o autoloading do framework.
     *
     * @var array<int, string>
     */
    protected $except = [];
}

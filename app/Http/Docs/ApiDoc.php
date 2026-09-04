<?php

namespace App\Http\Docs;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Amura Suporte API',
    version: '1.0.0',
    description: 'Documentação da API do sistema de suporte Amura',
    contact: new OA\Contact(email: 'suporte@amura.com.br')
)]
#[OA\Server(
    url: 'http://localhost',
    description: 'Servidor de desenvolvimento'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Laravel Sanctum token authentication'
)]
#[OA\SecurityScheme(
    securityScheme: 'integration_api_key',
    type: 'apiKey',
    in: 'header',
    name: 'X-API-Key',
    description: 'Chave de API para integração servidor-a-servidor com o sistema financeiro. Enviada no cabeçalho X-API-Key em todas as requisições.'
)]
#[OA\Tag(
    name: 'Integração - Financeiro',
    description: 'Endpoints consumidos pelo sistema financeiro (inbound). Autenticação por API key.'
)]
class ApiDoc {}

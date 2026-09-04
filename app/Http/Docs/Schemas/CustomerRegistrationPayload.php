<?php

declare(strict_types=1);

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

/**
 * Contrato do payload de cadastro de cliente enviado pelo sistema financeiro.
 *
 * Esta classe existe apenas para documentação OpenAPI (L5-Swagger) — não é
 * instanciada em runtime.
 *
 * Versão 2 do payload — principais mudanças:
 *   - `codigo_empresarial` removido (substituído por `business_group`)
 *   - `city_registration` agora recebe o Código IBGE do município
 *   - `state_registration` adicionado para a Inscrição Estadual (IE)
 *   - `business_group` adicionado como objeto com `code` e `name`
 *   - `contacts` adicionado como lista opcional de até dois contatos
 */
#[OA\Schema(
    schema: 'CustomerRegistrationPayload',
    title: 'Cadastro de Cliente (Financeiro)',
    description: 'Dados enviados pelo sistema financeiro ao cadastrar ou atualizar um cliente no suporte.',
    type: 'object',
    required: ['id', 'name', 'cnpj', 'business_group'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', description: 'Identificador do cliente no sistema de origem (financeiro).', example: 154),
        new OA\Property(property: 'name', type: 'string', description: 'Razão Social.', example: 'Isabelle e Alícia Joalheria ME'),
        new OA\Property(property: 'trade_name', type: 'string', description: 'Nome Fantasia.', example: 'Isabelle e Alícia Joalheria ME'),
        new OA\Property(property: 'cnpj', type: 'string', description: 'CNPJ (somente dígitos, 14 posições).', example: '18587938000115'),
        new OA\Property(property: 'city_registration', type: 'string', pattern: '^\d{7}$', description: 'Código IBGE do município com 7 dígitos (ex: 3205309 = Vitória/ES).', example: '3205309'),
        new OA\Property(property: 'state_registration', type: 'string', description: 'Inscrição Estadual (IE).', example: '64007281-0'),
        new OA\Property(property: 'telephone_1', type: 'string', description: 'Telefone de contato 1.', example: '2725929137'),
        new OA\Property(property: 'telephone_2', type: 'string', description: 'Telefone de contato 2.', example: '27983743841'),
        new OA\Property(property: 'logradouro', type: 'string', description: 'Endereço — logradouro.', example: 'Beco Almir Barbosa'),
        new OA\Property(property: 'numero', type: 'string', description: 'Endereço — número.', example: '685'),
        new OA\Property(property: 'complemento', type: 'string', description: 'Endereço — complemento.', nullable: true, example: ''),
        new OA\Property(property: 'bairro', type: 'string', description: 'Endereço — bairro.', example: 'Jesus de Nazareth'),
        new OA\Property(property: 'city', type: 'string', description: 'Cidade.', example: 'Vitória'),
        new OA\Property(property: 'state_id', type: 'integer', description: 'Identificador da UF (FK para a tabela de estados).', example: 32),
        new OA\Property(property: 'postal_code', type: 'string', description: 'CEP (somente dígitos).', example: '29052046'),
        new OA\Property(property: 'notes_2', type: 'string', description: 'Observações.', nullable: true, example: ''),
        new OA\Property(property: 'email', type: 'string', description: 'Email de contato principal.', nullable: true, example: 'contato@empresa.com.br'),
        new OA\Property(property: 'software_id', type: 'integer', description: 'Id do software associado ao cliente.', nullable: true, example: 1),
        new OA\Property(property: 'contact_name', type: 'string', description: 'Compatibilidade legada: nome do contato principal.', nullable: true, deprecated: true, example: 'João Silva'),
        new OA\Property(property: 'contact_email', type: 'string', description: 'Compatibilidade legada: e-mail do contato principal.', nullable: true, deprecated: true, example: 'joao.silva@empresa.com.br'),
        new OA\Property(
            property: 'contacts',
            description: 'Até dois contatos mantidos pelo Financeiro. Quando enviado, substitui somente os contatos anteriormente sincronizados pelo Financeiro.',
            type: 'array',
            maxItems: 2,
            nullable: true,
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Maria Comunicação'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'maria.comunicacao@empresa.com.br'),
                ],
            ),
        ),
        new OA\Property(
            property: 'business_group',
            type: 'object',
            description: 'Grupo Empresarial ao qual o cliente pertence. Obrigatório em cadastros e alterações.',
            required: ['code', 'name'],
            properties: [
                new OA\Property(property: 'code', type: 'string', description: 'Código único do grupo gerado pelo financeiro.', example: 'BFC27A6401563A'),
                new OA\Property(property: 'name', type: 'string', description: 'Nome do Grupo Empresarial.', example: 'Grupo Empresarial Exemplo'),
            ]
        ),
    ],
    example: [
        'id' => 154,
        'name' => 'Isabelle e Alícia Joalheria ME',
        'trade_name' => 'Isabelle e Alícia Joalheria ME',
        'cnpj' => '18587938000115',
        'city_registration' => '3205309',
        'state_registration' => '64007281-0',
        'telephone_1' => '2725929137',
        'telephone_2' => '27983743841',
        'logradouro' => 'Beco Almir Barbosa',
        'numero' => '685',
        'complemento' => '',
        'bairro' => 'Jesus de Nazareth',
        'city' => 'Vitória',
        'state_id' => 32,
        'postal_code' => '29052046',
        'notes_2' => '',
        'email' => 'contato@empresa.com.br',
        'software_id' => 1,
        'contacts' => [
            ['name' => 'Maria Comunicação', 'email' => 'maria.comunicacao@empresa.com.br'],
            ['name' => 'Carlos Cobrança', 'email' => 'carlos.cobranca@empresa.com.br'],
        ],
        'business_group' => [
            'code' => 'BFC27A6401563A',
            'name' => 'Grupo Empresarial Exemplo',
        ],
    ],
)]
class CustomerRegistrationPayload {}

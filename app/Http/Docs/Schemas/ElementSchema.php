<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ElementResource',
    title: 'Element Resource',
    description: 'Representação de um elemento de formulário CRM',
    type: 'object'
)]
class ElementSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 1)]
    public int $id;

    #[OA\Property(property: 'name', type: 'string', example: 'feedback_type')]
    public string $name;

    #[OA\Property(property: 'label', type: 'string', example: 'Tipo de Feedback')]
    public string $label;

    #[OA\Property(property: 'sort_order', type: 'integer', example: 10)]
    public int $sort_order;
}
<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'FeedbackCountResource',
    title: 'Feedback Count Resource',
    description: 'Dados do agente com contagem de feedbacks',
    type: 'object'
)]
class FeedbackCountResourceSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 5)]
    public int $id;

    #[OA\Property(property: 'name', type: 'string', example: 'Agente Silva')]
    public string $name;

    #[OA\Property(property: 'feedbacks_count', type: 'integer', example: 42)]
    public int $feedbacks_count;
}
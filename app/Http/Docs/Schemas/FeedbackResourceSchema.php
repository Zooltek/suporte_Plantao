<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'FeedbackResource',
    title: 'Feedback Resource',
    type: 'object'
)]
class FeedbackResourceSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 1)]
    public int $id;

    #[OA\Property(property: 'comment', type: 'string', example: 'Excelente atendimento')]
    public string $comment;

    #[OA\Property(property: 'completed_at', type: 'string', format: 'date-time')]
    public string $completed_at;

    #[OA\Property(property: 'customer', ref: '#/components/schemas/CategoryResource')] // Reaproveitando estrutura de cliente/empresa se houver
    public object $customer;
}
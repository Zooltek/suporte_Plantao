<?php

namespace App\Http\Resources\Admin\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DepartmentResource',
    properties: [
        new OA\Property(property: 'id',          type: 'integer', example: 1),
        new OA\Property(property: 'name',        type: 'string',  example: 'Suporte Técnico'),
        new OA\Property(property: 'description', type: 'string',  nullable: true, example: 'Equipe de suporte ao cliente'),
        new OA\Property(property: 'labels_count', type: 'integer', example: 3),
    ]
)]
class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'labels'      => $this->whenLoaded('labels', fn () => $this->labels->map(fn ($l) => [
                'id'   => $l->id,
                'name' => $l->name,
            ])),
        ];
    }
}

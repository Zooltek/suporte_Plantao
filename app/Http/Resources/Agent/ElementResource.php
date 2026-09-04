<?php

namespace App\Http\Resources\Agent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ElementResource',
    title: 'Element Resource',
    description: 'Recurso que representa um Elemento do cronograma',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'pgto_cartao'),
        new OA\Property(property: 'label', type: 'string', example: 'Cartão'),
        new OA\Property(property: 'type', type: 'string', example: 'checkbox'),
        new OA\Property(property: 'module_id', type: 'integer', example: 1),
        new OA\Property(property: 'group_id', type: 'integer', example: 2),
        new OA\Property(
            property: 'group_name',
            type: 'string',
            example: 'Pagamento',
            description: 'Nome do grupo, caso o relacionamento tenha sido carregado'
        ),
    ]
)]
class ElementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'label'      => $this->label,
            'type'       => $this->type,
            'module_id'  => $this->module_id,
            'group_id'   => $this->group_id,
            'group_name' => $this->whenLoaded('group', fn () => $this->group->name),
        ];
    }
}
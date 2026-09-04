<?php

namespace App\Http\Docs\Schemas\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CategoryResource',
    title: 'Category Resource',
    type: 'object'
)]
class CategorySchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 1)]
    public int $id;

    #[OA\Property(property: 'category_id', type: 'integer', example: 1)]
    public int $category_id;

    #[OA\Property(property: 'name', type: 'string', example: 'Suporte Técnico')]
    public string $name;

    #[OA\Property(property: 'permalink', type: 'string', example: 'suporte-tecnico')]
    public string $permalink;

    #[OA\Property(property: 'description_text', type: 'string', example: 'Categoria para suporte técnico')]
    public ?string $description_text;

    #[OA\Property(property: 'priority', type: 'string', example: 'high')]
    public string $priority;

    #[OA\Property(property: 'priority_label', type: 'string', example: 'Alta')]
    public string $priority_label;

    #[OA\Property(property: 'priority_color', type: 'string', example: 'text-orange-600')]
    public string $priority_color;

    #[OA\Property(property: 'parent_id', type: 'integer', nullable: true, example: null)]
    public ?int $parent_id;

    #[OA\Property(property: 'parent', ref: '#/components/schemas/CategoryResource', nullable: true)]
    public ?object $parent;

    #[OA\Property(
        property: 'children',
        type: 'array',
        items: new OA\Items(ref: '#/components/schemas/CategoryResource'),
        example: [
            [
                "id" => 2,
                "category_id" => 2,
                "name" => "Subcategoria Exemplo",
                "permalink" => "subcategoria-exemplo",
                "description_text" => "Exemplo de subcategoria",
                "priority" => "low",
                "priority_label" => "Baixa",
                "priority_color" => "text-green-600",
                "parent_id" => 1,
                "children" => []
            ]
        ]
    )]
    public array $children;

    #[OA\Property(property: 'created_at', type: 'string', format: 'date-time')]
    public string $created_at;

    #[OA\Property(property: 'updated_at', type: 'string', format: 'date-time')]
    public string $updated_at;
}
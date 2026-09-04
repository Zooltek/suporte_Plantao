<?php

namespace App\Http\Docs\Schemas\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserSchema',
    title: 'User Resource',
    type: 'object'
)]
class UserSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 1)]
    public int $id;

    #[OA\Property(property: 'name', type: 'string', example: 'João Silva')]
    public string $name;

    #[OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao@amura.com.br')]
    public string $email;

    #[OA\Property(property: 'department_id', type: 'integer', example: 2)]
    public int $department_id;

    #[OA\Property(
        property: 'department', 
        ref: '#/components/schemas/DepartmentResource'
    )]
    public object $department;
}
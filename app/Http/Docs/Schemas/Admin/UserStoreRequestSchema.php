<?php

namespace App\Http\Docs\Schemas\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserStoreRequest',
    title: 'User Store Request',
    required: ['name', 'email', 'password', 'department_id'],
    type: 'object'
)]
class UserStoreRequestSchema
{
    #[OA\Property(property: 'name', type: 'string', example: 'João Silva')]
    public string $name;

    #[OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao@amura.com.br')]
    public string $email;

    #[OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123')]
    public string $password;

    #[OA\Property(property: 'department_id', type: 'integer', example: 2)]
    public int $department_id;
}

#[OA\Schema(
    schema: 'UserUpdateRequest',
    title: 'User Update Request',
    type: 'object'
)]
class UserUpdateRequestSchema
{
    #[OA\Property(property: 'name', type: 'string', example: 'João Silva Updated')]
    public string $name;

    #[OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao.novo@amura.com.br')]
    public string $email;

    #[OA\Property(property: 'department_id', type: 'integer', example: 3)]
    public int $department_id;
}
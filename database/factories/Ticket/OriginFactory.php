<?php

namespace Database\Factories\Ticket;

use App\Models\Ticket\Origin;
use Illuminate\Database\Eloquent\Factories\Factory;

class OriginFactory extends Factory
{
    protected $model = Origin::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
        ];
    }
}

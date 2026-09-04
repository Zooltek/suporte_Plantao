<?php

namespace Database\Factories\Ticket;

use App\Models\Ticket\Priority;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriorityFactory extends Factory
{
    protected $model = Priority::class;

    public function definition(): array
    {
        return [
            'name'  => $this->faker->unique()->word(),
            'color' => $this->faker->hexColor(),
        ];
    }
}

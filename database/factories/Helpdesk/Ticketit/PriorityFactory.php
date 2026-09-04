<?php

namespace Database\Factories\Helpdesk\Ticketit;

use App\Models\Helpdesk\Ticketit\Priority;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriorityFactory extends Factory
{
    protected $model = Priority::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
            'color' => $this->faker->hexColor,
        ];
    }
}

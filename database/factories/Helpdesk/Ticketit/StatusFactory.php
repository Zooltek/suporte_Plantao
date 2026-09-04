<?php

namespace Database\Factories\Helpdesk\Ticketit;

use App\Models\Helpdesk\Ticket\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatusFactory extends Factory
{
    protected $model = Status::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
            'color' => $this->faker->hexColor,
        ];
    }
}

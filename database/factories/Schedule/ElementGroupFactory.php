<?php

namespace Database\Factories\Schedule;

use App\Models\Schedule\ElementGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class ElementGroupFactory extends Factory
{
    protected $model = ElementGroup::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
        ];
    }
}

<?php

namespace Database\Factories\Tasks;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tasks\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'    => $this->faker->unique()->words(2, true),
            'color'   => $this->faker->hexColor(),
            'status'  => 1,
            'user_id' => \App\Models\User::factory(),
        ];
    }
}

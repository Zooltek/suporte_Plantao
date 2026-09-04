<?php

namespace Database\Factories;

use App\Models\CustomerGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerGroup>
 */
class CustomerGroupFactory extends Factory
{
    protected $model = CustomerGroup::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'hash' => $this->faker->unique()->sha256,
            'status' => $this->faker->boolean(80),
        ];
    }
}

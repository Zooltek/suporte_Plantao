<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'contract_number' => strtoupper($this->faker->bothify('CT-####')),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->optional()->date(),
            'value' => $this->faker->randomFloat(2, 1000, 50000),
        ];
    }
}

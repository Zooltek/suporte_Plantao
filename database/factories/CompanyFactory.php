<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Software;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $stateCount = State::count();
        $softwareId = Software::query()->inRandomOrder()->value('id');

        return [
            'name' => $this->faker->company().' '.$this->faker->companySuffix(),
            'trade_name' => $this->faker->company(),
            'codigo_empresarial' => $this->faker->optional()->bothify('??#######'),
            'cnpj' => $this->faker->unique()->numerify('##.###.###/####-##'),
            'city_registration' => $this->faker->optional()->numerify('#######'),
            'state_registration' => $this->faker->optional()->numerify('###.###.###-#'),
            'customer_group_id' => 1,
            'state_id' => $stateCount > 0 ? $this->faker->numberBetween(1, $stateCount) : 1,
            'software_id' => $softwareId ?? Software::factory(),
            'contact_name' => $this->faker->name(),
            'contact_email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->numerify('(##) ####-####'),
            'telephone_2' => $this->faker->optional()->numerify('(##) 9####-####'),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'bairro' => 'Centro',
            'observations' => null,
            'has_ecommerce' => false,
            'has_crm' => false,
            'has_tef' => false,
            'is_active' => true,
            'financial_irregular' => false,
            'created_at' => now(),
        ];
    }
}

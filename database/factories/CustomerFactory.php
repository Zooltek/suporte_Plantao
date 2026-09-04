<?php

namespace Database\Factories;

use App\Models\Software;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        $cities = [
            'São Paulo', 'Rio de Janeiro', 'Belo Horizonte', 'Curitiba',
            'Porto Alegre', 'Florianópolis', 'Blumenau', 'Joinville',
            'Campinas', 'Guarulhos', 'Maringá', 'Londrina',
            'Criciúma', 'Chapecó', 'Lages', 'Itajaí',
        ];

        $bairros = [
            'Centro', 'Jardim América', 'Vila Nova', 'Boa Vista',
            'Santa Catarina', 'São João', 'Bela Vista', 'Industrial',
            'Parque das Flores', 'Residencial Park', 'Alto da Serra', 'São Lucas',
        ];

        $stateCount = State::count();
        $softwareId = Software::query()->inRandomOrder()->value('id');

        return [
            'name' => $this->faker->company().' '.$this->faker->companySuffix(),
            'trade_name' => $this->faker->company(),
            'codigo_empresarial' => $this->faker->optional()->bothify('??#######'),
            'cnpj' => $this->faker->unique()->numerify('##.###.###/####-##'),
            'city_registration' => $this->faker->optional()->numerify('#######'),
            'state_registration' => $this->faker->optional()->numerify('###.###.###-#'),
            'customer_group_id' => $this->faker->numberBetween(1, 3),
            'state_id' => $stateCount > 0 ? $this->faker->numberBetween(1, $stateCount) : 1,
            'software_id' => $softwareId ?? Software::factory(),
            'contact_name' => $this->faker->name(),
            'contact_email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->numerify('(##) ####-####'),
            'telephone_2' => $this->faker->optional()->numerify('(##) 9####-####'),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->randomElement($cities),
            'bairro' => $this->faker->randomElement($bairros),
            'observations' => $this->faker->optional(0.3)->sentence(),
            'has_ecommerce' => $this->faker->boolean(30),
            'has_crm' => $this->faker->boolean(20),
            'has_tef' => $this->faker->boolean(40),
            'is_active' => $this->faker->boolean(85),
            'financial_irregular' => $this->faker->boolean(10),
            'created_at' => now(),
        ];
    }
}

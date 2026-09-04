<?php

namespace Database\Factories;

use App\Models\CompanyModuleType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyModuleTypeFactory extends Factory
{
    protected $model = CompanyModuleType::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
        return [
            'name'          => ucfirst($name),
            'slug'          => Str::slug($name, '_'),
            'is_active'     => true,
            'sort_order'    => $this->faker->numberBetween(0, 10),
            'rat_module_id' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /**
     * Associa o módulo a um template RAT existente.
     * Uso: CompanyModuleType::factory()->withRatModule($ratModule)->create()
     */
    public function withRatModule(\App\Models\Schedule\Module $ratModule): static
    {
        return $this->state(['rat_module_id' => $ratModule->id]);
    }
}

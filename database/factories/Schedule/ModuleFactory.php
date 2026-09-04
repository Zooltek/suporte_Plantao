<?php

namespace Database\Factories\Schedule;

use App\Models\Schedule\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModuleFactory extends Factory
{
    protected $model = Module::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->unique()->word() . '-' . strtoupper($this->faker->lexify('???')),
            'description' => $this->faker->optional(0.7)->sentence(),
            'project'     => $this->faker->randomElement(['EasyMaster', 'EasyPDV', 'Geral', null]),
        ];
    }
}

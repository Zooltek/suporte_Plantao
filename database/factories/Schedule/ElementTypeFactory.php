<?php

namespace Database\Factories\Schedule;

use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

class ElementTypeFactory extends Factory
{
    protected $model = ElementType::class;

    public function definition(): array
    {
        return [
            'name'      => $this->faker->unique()->slug(2),
            'label'     => $this->faker->words(3, true),
            'type'      => 'checkbox',
            'module_id' => Module::factory(),
            'group_id'  => ElementGroup::factory(),
        ];
    }
}

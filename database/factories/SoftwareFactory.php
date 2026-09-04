<?php

namespace Database\Factories;

use App\Models\Software;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Software>
 */
class SoftwareFactory extends Factory
{
    protected $model = Software::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'version' => $this->faker->numerify('##.##.##'),
            'status' => true,
        ];
    }
}

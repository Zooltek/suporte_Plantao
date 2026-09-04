<?php
namespace Database\Factories;

use App\Models\CategoryDescription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryDescriptionFactory extends Factory
{
    protected $model = CategoryDescription::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            // category_id será passado na criação
            'name'        => $name,
            'permalink'   => Str::slug($name),
            'description' => $this->faker->sentence(),
        ];
    }
}

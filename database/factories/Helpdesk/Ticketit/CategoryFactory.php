<?php

namespace Database\Factories\Helpdesk\Ticketit;

use App\Models\Category;
use App\Models\CategoryDescription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'parent_id' => 0, // Categoria raiz por padrão
            'priority' => $this->faker->randomElement(['low', 'high', 'urgent']),
        ];
    }

    public function withDescription(?string $name = null): static
    {
        return $this->afterCreating(function (Category $category) use ($name): void {
            $label = $name ?: $this->faker->words(2, true);

            CategoryDescription::query()->updateOrCreate(
                ['category_id' => $category->category_id],
                [
                    'name' => $label,
                    'permalink' => Str::slug($label),
                    'description' => $label,
                ],
            );
        });
    }
}

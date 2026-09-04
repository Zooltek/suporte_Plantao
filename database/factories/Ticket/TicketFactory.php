<?php

namespace Database\Factories\Ticket;

use App\Models\Category;
use App\Models\CategoryDescription;
use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket\Origin;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * Definição base do Ticket.
     *
     * Atributos pesados (category_id, company_id, origin_id, author_id) usam
     * closures lazy — só são resolvidos quando NÃO há override no `create()`.
     * Isso evita o bug de criar entidades órfãs no banco (ex.: Category sem
     * CategoryDescription) quando os seeders fornecem ids explícitos.
     */
    public function definition(): array
    {
        $createdAt = $this->faker->dateTimeBetween('-30 days', '-1 hour');

        return [
            'subject' => $this->faker->sentence(5),
            'content' => $this->faker->paragraph(2),
            'author_id' => fn () => User::factory()->create()->id,
            'user_id' => fn (array $attrs) => $attrs['author_id'] ?? User::factory()->create()->id,
            'agent_id' => null,
            'status_id' => 1,
            'priority_id' => 1,
            'category_id' => fn () => $this->createCategoryWithDescription()->category_id,
            'sub_category_id' => null,
            // Simula o estado de produção pré-Fase 2: o ticket nasce já com
            // department_id preenchido (do Suporte, default), o que permite
            // ao backfill detectar movimentações para o setor da categoria.
            'department_id' => $this->defaultDepartmentId(),
            'company_id' => fn () => Company::factory()->create()->id,
            'origin_id' => fn () => Origin::factory()->create()->id,
            'contact' => strtoupper($this->faker->name()),
            'completed_at' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    /**
     * Cria uma Category já com CategoryDescription — evita registros órfãos
     * que apareceriam como "Sem nome" na tela de gerenciamento de categorias.
     */
    private function createCategoryWithDescription(): Category
    {
        $category = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        $name = $this->faker->unique()->words(2, true);
        CategoryDescription::query()->updateOrCreate(
            ['category_id' => $category->category_id],
            [
                'name' => $name,
                'permalink' => Str::slug($name).'-'.$category->category_id,
                'description' => 'Categoria gerada por TicketFactory',
            ],
        );

        return $category;
    }

    private function defaultDepartmentId(): ?int
    {
        $id = Department::query()
            ->where('name', 'like', '%Suporte%')
            ->orderBy('id')
            ->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * Ticket fechado/resolvido hoje.
     */
    public function resolved(): static
    {
        return $this->state(fn () => [
            'status_id' => Status::where('id', 3)->value('id') ?? 3,
            'completed_at' => now()->subHours(rand(1, 8)),
        ]);
    }

    /**
     * Ticket pendente/em aberto, criado hoje.
     */
    public function openToday(): static
    {
        return $this->state(fn () => [
            'status_id' => collect([1, 2, 4])->random(),
            'completed_at' => null,
            'created_at' => now()->subHours(rand(1, 10)),
            'updated_at' => now(),
        ]);
    }

    /**
     * Ticket pendente antigo (accumulated backlog).
     */
    public function oldPending(): static
    {
        return $this->state(fn () => [
            'status_id' => collect([1, 2, 4])->random(),
            'completed_at' => null,
            'created_at' => now()->subDays(rand(3, 30)),
            'updated_at' => now()->subDays(rand(1, 3)),
        ]);
    }
}

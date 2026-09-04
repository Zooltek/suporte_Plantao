<?php

namespace Database\Seeders\Helpdesk\Ticketit;

use App\Models\Category;
use App\Models\CategoryDescription;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Conjunto enxuto de categorias condizentes com um sistema de suporte
     * a ERP. Cada categoria-pai já carrega o departamento responsável,
     * alimentando o TicketDepartmentResolver com o roteamento correto.
     *
     * Cada item: 'Nome' => ['department' => fragmento|null, 'children' => [...]]
     * Subcategorias herdam o departamento do pai (override opcional aqui).
     */
    private const CATEGORIES = [
        'Suporte Técnico' => [
            'department' => 'Suporte',
            'children' => ['Erro de Sistema', 'Performance / Lentidão'],
        ],
        'Financeiro' => [
            'department' => 'Financeiro',
            'children' => ['Faturamento', 'Cobrança'],
        ],
        'Comercial' => [
            'department' => 'Comercial',
            'children' => ['Renovação Contratual', 'Negociação'],
        ],
        'Implantação' => [
            'department' => 'Suporte',
            'children' => ['Migração de Dados'],
        ],
        'Treinamento' => [
            'department' => null,
            'children' => [],
        ],
    ];

    public function run(): void
    {
        if (! $this->shouldSeedDefaultCategories()) {
            $this->command?->warn('CategorySeeder ignorado: SEED_DEFAULT_CATEGORIES=false');

            return;
        }

        foreach (self::CATEGORIES as $nomePai => $config) {
            $departmentId = $this->resolveDepartmentId($config['department']);

            $pai = $this->upsertCategory(
                parentId: 0,
                nome: $nomePai,
                prioridade: Category::PRIORITY_HIGH,
                descricao: "Chamados relacionados a $nomePai",
                departmentId: $departmentId,
            );

            foreach ($config['children'] as $nomeFilho) {
                $this->upsertCategory(
                    parentId: $pai->category_id,
                    nome: $nomeFilho,
                    prioridade: Category::PRIORITY_LOW,
                    descricao: "Subcategoria: $nomeFilho",
                    departmentId: $departmentId,
                );
            }
        }

        $this->command->info('Categorias criadas com sucesso!');
    }

    private function upsertCategory(
        int $parentId,
        string $nome,
        string $prioridade,
        ?string $descricao = null,
        ?int $departmentId = null,
    ): Category {
        $categoria = Category::query()
            ->where('parent_id', $parentId)
            ->whereHas('description', function ($query) use ($nome) {
                $query->where('name', $nome);
            })
            ->first();

        $attrs = [
            'parent_id' => $parentId,
            'priority' => $prioridade,
            'department_id' => $departmentId,
        ];

        if (! $categoria) {
            $categoria = Category::create($attrs);
        } else {
            $categoria->update($attrs);
        }

        CategoryDescription::updateOrCreate(
            ['category_id' => $categoria->category_id],
            [
                'name' => $nome,
                'permalink' => Str::slug($nome),
                'description' => $descricao,
            ]
        );

        return $categoria->fresh(['description']);
    }

    private function resolveDepartmentId(?string $nameFragment): ?int
    {
        if ($nameFragment === null) {
            return null;
        }

        $id = Department::query()
            ->where('name', 'like', "%{$nameFragment}%")
            ->orderBy('id')
            ->value('id');

        return $id ? (int) $id : null;
    }

    private function shouldSeedDefaultCategories(): bool
    {
        $value = env('SEED_DEFAULT_CATEGORIES', true);

        if (is_bool($value)) {
            return $value;
        }

        return ! in_array(strtolower((string) $value), ['0', 'false', 'off', 'no'], true);
    }
}

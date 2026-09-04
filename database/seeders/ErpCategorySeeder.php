<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryDescription;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Popula categorias realistas para ambiente de testes do suporte ERP.
 * Seguro para re-executar: usa upsert por nome.
 *
 * As categorias-pai que correspondem a departamentos (Financeiro, Suporte
 * Técnico, Vendas/Comercial) já entram com department_id preenchido,
 * alimentando o TicketDepartmentResolver com a rota correta desde o seed.
 */
class ErpCategorySeeder extends Seeder
{
    /**
     * Estrutura: 'Categoria Pai' => ['department' => fragmento|null, 'children' => [...]]
     * Quando `department` é null, a categoria não força roteamento — o ticket
     * cai no agente/canal/fallback.
     */
    private const CATEGORIES = [
        'Financeiro' => [
            'department' => 'Financeiro',
            'children' => [
                'Contas a Pagar',
                'Contas a Receber',
                'Fluxo de Caixa',
                'Conciliação Bancária',
                'Fechamento de Caixa',
            ],
        ],
        'Fiscal / Tributário' => [
            'department' => null,
            'children' => [
                'Emissão de NF-e',
                'SPED / EFD',
                'Apuração de Impostos',
                'Notas de Serviço (NFS-e)',
                'Manifesto de Carga (MDF-e)',
            ],
        ],
        'Estoque' => [
            'department' => null,
            'children' => [
                'Entrada de Mercadoria',
                'Saída / Expedição',
                'Inventário',
                'Custo de Produtos',
                'Transferência entre Filiais',
            ],
        ],
        'Vendas e Faturamento' => [
            'department' => 'Comercial',
            'children' => [
                'Pedidos de Venda',
                'Faturamento',
                'Comissões',
                'Devoluções',
                'Tabela de Preços',
            ],
        ],
        'Compras' => [
            'department' => null,
            'children' => [
                'Cotações',
                'Pedidos de Compra',
                'Recebimento de Mercadoria',
                'Cadastro de Fornecedores',
            ],
        ],
        'Suporte Técnico' => [
            'department' => 'Suporte',
            'children' => [
                'Instalação / Atualização',
                'Performance / Lentidão',
                'Banco de Dados',
                'Integração / API',
                'Backup e Restauração',
            ],
        ],
        'Cadastros Gerais' => [
            'department' => null,
            'children' => [
                'Clientes',
                'Produtos',
                'Usuários e Permissões',
                'Parâmetros do Sistema',
            ],
        ],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $parentName => $config) {
            $departmentId = $this->resolveDepartmentId($config['department']);

            $parent = $this->upsertCategory(
                parentId: 0,
                name: $parentName,
                priority: Category::PRIORITY_HIGH,
                description: "Suporte relacionado a $parentName",
                departmentId: $departmentId,
            );

            foreach ($config['children'] as $childName) {
                $this->upsertCategory(
                    parentId: $parent->category_id,
                    name: $childName,
                    priority: Category::PRIORITY_LOW,
                    description: "Subcategoria: $childName",
                    departmentId: $departmentId,
                );
            }
        }

        $this->command->info('ErpCategorySeeder: '.count(self::CATEGORIES).' categorias-pai com sub-categorias criadas/atualizadas.');
    }

    private function upsertCategory(
        int $parentId,
        string $name,
        string $priority,
        ?string $description = null,
        ?int $departmentId = null,
    ): Category {
        $category = Category::query()
            ->where('parent_id', $parentId)
            ->whereHas('description', fn ($q) => $q->where('name', $name))
            ->first();

        $attrs = [
            'parent_id' => $parentId,
            'priority' => $priority,
            'status' => 1,
            'visible' => 1,
            'department_id' => $departmentId,
        ];

        if (! $category) {
            $category = Category::create($attrs);
        } else {
            $category->update([
                'priority' => $priority,
                'department_id' => $departmentId,
            ]);
        }

        CategoryDescription::updateOrCreate(
            ['category_id' => $category->category_id],
            [
                'name' => $name,
                'permalink' => Str::slug($name),
                'description' => $description,
            ]
        );

        return $category->fresh(['description']);
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
}

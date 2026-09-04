<?php

use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;
use Database\Seeders\Schedule\VendasModuleSeeder;

// ─── VendasModuleSeeder ───────────────────────────────────────────────────────

describe('VendasModuleSeeder — integridade', function () {

    it('cria o módulo "Vendas RC" com project "EasyMaster"', function () {
        $this->seed(VendasModuleSeeder::class);

        $this->assertDatabaseHas('schedule_record_module', [
            'name'    => 'Vendas RC',
            'project' => 'EasyMaster',
        ]);
    });

    it('cria exatamente 6 grupos', function () {
        $this->seed(VendasModuleSeeder::class);

        $expectedGroups = [
            'Geral',
            'Venda ECF/NFE/NFC-e',
            'Pagamento',
            'Operações de Caixa',
            'Devolução de Venda',
            'Técnico e Segurança',
        ];

        foreach ($expectedGroups as $name) {
            $this->assertDatabaseHas('schedule_record_element_group', ['name' => $name]);
        }

        expect(ElementGroup::whereIn('name', $expectedGroups)->count())->toBe(6);
    });

    it('cria 36 elementos no total (5 + 7 + 11 + 8 + 3 + 2)', function () {
        $this->seed(VendasModuleSeeder::class);

        $module = Module::where('name', 'Vendas RC')->first();

        expect(ElementType::where('module_id', $module->id)->count())->toBe(36);
    });

    it('todos os elementos têm type = "checkbox"', function () {
        $this->seed(VendasModuleSeeder::class);

        $module = Module::where('name', 'Vendas RC')->first();

        $nonCheckbox = ElementType::where('module_id', $module->id)
            ->where('type', '!=', 'checkbox')
            ->count();

        expect($nonCheckbox)->toBe(0);
    });

    it('spot-check: pgto_cartao tem label "Cartão" no grupo "Pagamento"', function () {
        $this->seed(VendasModuleSeeder::class);

        $element = ElementType::where('name', 'pgto_cartao')->with('group')->first();

        expect($element)->not->toBeNull()
            ->and($element->label)->toBe('Cartão')
            ->and($element->group?->name)->toBe('Pagamento');
    });

    it('spot-check: backup tem label "Backup" no grupo "Técnico e Segurança"', function () {
        $this->seed(VendasModuleSeeder::class);

        $element = ElementType::where('name', 'backup')->with('group')->first();

        expect($element)->not->toBeNull()
            ->and($element->label)->toBe('Backup')
            ->and($element->group?->name)->toBe('Técnico e Segurança');
    });

});

// ─── VendasModuleSeeder — idempotência ────────────────────────────────────────

describe('VendasModuleSeeder — idempotência', function () {

    it('rodar o seeder duas vezes não duplica registros', function () {
        $this->seed(VendasModuleSeeder::class);
        $this->seed(VendasModuleSeeder::class);

        $module = Module::where('name', 'Vendas RC')->first();

        expect(Module::where('name', 'Vendas RC')->count())->toBe(1)
            ->and(ElementType::where('module_id', $module->id)->count())->toBe(36);
    });

});

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $atendimentoId = DB::table('solutions_category')
            ->join(
                'solutions_category_description',
                'solutions_category.category_id',
                '=',
                'solutions_category_description.category_id'
            )
            ->whereRaw('LOWER(solutions_category_description.name) = ?', ['atendimento'])
            ->where('solutions_category.parent_id', 0)
            ->value('solutions_category.category_id');

        if (! $atendimentoId) {
            return;
        }

        $alreadyExists = DB::table('solutions_category')
            ->join(
                'solutions_category_description',
                'solutions_category.category_id',
                '=',
                'solutions_category_description.category_id'
            )
            ->whereRaw('LOWER(solutions_category_description.name) = ?', ['suporte'])
            ->where('solutions_category.parent_id', $atendimentoId)
            ->exists();

        if ($alreadyExists) {
            return;
        }

        $categoryId = DB::table('solutions_category')->insertGetId([
            'parent_id' => $atendimentoId,
            'priority' => 'low',
            'status' => 1,
            'visible' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('solutions_category_description')->insert([
            'category_id' => $categoryId,
            'name' => 'Suporte',
            'permalink' => Str::slug('Suporte'),
            'description' => 'Subcategoria: chamados encaminhados ao Suporte Técnico.',
        ]);
    }

    public function down(): void
    {
        // Sem rollback automático: a subcategoria pode ter sido associada a tickets.
        // Caso necessário reverter, remova manualmente após realocar os tickets.
    }
};

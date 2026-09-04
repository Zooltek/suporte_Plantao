<?php

namespace Database\Seeders;

use App\Models\Knowledge\KnowledgeArticle;
use Illuminate\Database\Seeder;

class KnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        if (KnowledgeArticle::count() > 0) {
            $this->command->info('KnowledgeSeeder: artigos já existem, pulando.');
            return;
        }

        // 15 artigos ativos com conteúdo realista de suporte ERP
        KnowledgeArticle::factory()->count(15)->create();

        // 2 artigos inativos (para testar filtro de status)
        KnowledgeArticle::factory()->inactive()->count(2)->create();

        $this->command->info('KnowledgeSeeder: 17 artigos criados (15 ativos, 2 inativos).');
    }
}

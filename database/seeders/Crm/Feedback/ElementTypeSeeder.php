<?php

namespace Database\Seeders\Crm\Feedback;

use App\Models\Crm\Feedback\ElementType;
use Illuminate\Database\Seeder;

class ElementTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Dados fake seguros
        ElementType::factory()->count(20)->create();

        // Registro fixo (controle total)
        ElementType::updateOrCreate(
            ['name' => 'satisfacao'],
            [
                'label'      => 'Nível de satisfação',
                'type'       => 'select',
                'data'       => json_encode([
                    'Muito satisfeito',
                    'Satisfeito',
                    'Neutro',
                    'Insatisfeito',
                ]),
                'sort_order' => 1,
            ]
        );

        $this->command->info('Tipos de elementos criados com sucesso!');
    }
}

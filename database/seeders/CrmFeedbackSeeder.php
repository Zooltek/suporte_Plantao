<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Crm\Feedback\Form;
use App\Models\Crm\Feedback\ElementType;

class CrmFeedbackSeeder extends Seeder
{
    public function run(): void
    {
        // Cria formulários base para habilitar a seleção na UI
        collect([
            ['name' => 'Satisfação Geral'],
            ['name' => 'Atendimento Suporte'],
            ['name' => 'NPS'],
        ])->each(fn ($data) => Form::firstOrCreate(['name' => $data['name']], $data));

        // Elementos globais (a tabela atual não relaciona por form_id)
        $elements = [
            [
                'label' => 'Nome',
                'name'  => 'nome',
                'type'  => 'text',
                'sort_order' => 1,
            ],
            [
                'label' => 'Email',
                'name'  => 'email',
                'type'  => 'text',
                'sort_order' => 2,
            ],
            [
                'label' => 'Nota',
                'name'  => 'nota',
                'type'  => 'number',
                'sort_order' => 3,
            ],
            [
                'label' => 'Comentário',
                'name'  => 'comentario',
                'type'  => 'textarea',
                'sort_order' => 4,
            ],
            [
                'label' => 'Canal de Atendimento',
                'name'  => 'canal_atendimento',
                'type'  => 'select',
                'data'  => 'Email,Telefone,WhatsApp,Portal',
                'sort_order' => 5,
            ],
        ];

        foreach ($elements as $element) {
            ElementType::updateOrCreate(
                ['name' => $element['name']],
                $element
            );
        }
    }
}

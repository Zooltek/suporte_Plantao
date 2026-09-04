<?php

namespace Database\Seeders;

use App\Models\Crm\Feedback;
use App\Models\Crm\Feedback\Form;
use App\Models\Crm\Feedback\ElementType;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Popula dados para as rotas:
 *   /crm?tab=feedbacks
 *   /crm?tab=feedbacks&form_id=1
 *   /crm?tab=feedbacks&form_id=2
 *   /crm?tab=feedbacks&form_id=4
 *
 * Garante 4 formulários, elementos vinculados por form_id e feedbacks.
 */
class CrmFeedbackFormsSeeder extends Seeder
{
    private array $forms = [
        1 => ['name' => 'Satisfação Geral'],
        2 => ['name' => 'Atendimento Suporte'],
        3 => ['name' => 'NPS'],
        4 => ['name' => 'Implantação'],
    ];

    private array $elementsByForm = [
        1 => [
            ['label' => 'Nível de satisfação', 'name' => 'satisfacao_geral', 'type' => 'select',   'data' => 'Muito satisfeito;Satisfeito;Neutro;Insatisfeito', 'sort_order' => 1],
            ['label' => 'Comentário geral',    'name' => 'comentario_geral', 'type' => 'textarea', 'data' => null,                                               'sort_order' => 2],
            ['label' => 'Recomendaria?',       'name' => 'recomendaria',     'type' => 'radio',    'data' => 'Sim;Não;Talvez',                                   'sort_order' => 3],
        ],
        2 => [
            ['label' => 'Velocidade do atendimento', 'name' => 'velocidade_atend',   'type' => 'select',   'data' => 'Ótimo;Bom;Regular;Ruim', 'sort_order' => 1],
            ['label' => 'Clareza nas respostas',     'name' => 'clareza_respostas',  'type' => 'select',   'data' => 'Ótimo;Bom;Regular;Ruim', 'sort_order' => 2],
            ['label' => 'Problema resolvido?',       'name' => 'problema_resolvido', 'type' => 'radio',    'data' => 'Sim;Não;Parcialmente',   'sort_order' => 3],
            ['label' => 'Sugestões de melhoria',     'name' => 'sugestoes_atend',    'type' => 'textarea', 'data' => null,                     'sort_order' => 4],
        ],
        3 => [
            ['label' => 'Nota NPS (0-10)',      'name' => 'nota_nps',       'type' => 'number',   'data' => null,          'sort_order' => 1],
            ['label' => 'Motivo da nota',       'name' => 'motivo_nps',     'type' => 'textarea', 'data' => null,          'sort_order' => 2],
            ['label' => 'Produto avaliado',     'name' => 'produto_nps',    'type' => 'select',   'data' => 'ERP;Portal;App Mobile;Suporte', 'sort_order' => 3],
        ],
        4 => [
            ['label' => 'Facilidade de implantação', 'name' => 'facilidade_impl',  'type' => 'select',   'data' => 'Muito fácil;Fácil;Difícil;Muito difícil', 'sort_order' => 1],
            ['label' => 'Suporte durante implant.',  'name' => 'suporte_impl',     'type' => 'select',   'data' => 'Ótimo;Bom;Regular;Ruim',                  'sort_order' => 2],
            ['label' => 'Treinamento adequado?',     'name' => 'treinamento_impl', 'type' => 'radio',    'data' => 'Sim;Não;Parcialmente',                    'sort_order' => 3],
            ['label' => 'Observações finais',        'name' => 'obs_impl',         'type' => 'textarea', 'data' => null,                                       'sort_order' => 4],
        ],
    ];

    public function run(): void
    {
        $this->command->info('🌱  CrmFeedbackFormsSeeder iniciando...');

        // 1. Garante os 4 formulários com IDs esperados
        $formMap = $this->ensureForms();

        // 2. Cria ElementTypes vinculados por form_id
        $this->seedElementTypes($formMap);

        // 3. Cria Feedbacks vinculados por form_id
        $this->seedFeedbacks($formMap);

        $this->command->info('✅  CrmFeedbackFormsSeeder concluído!');
        $this->command->info('   /crm?tab=feedbacks            → ' . Form::count() . ' formulários');
        $this->command->info('   ElementTypes criados          → ' . ElementType::whereNotNull('form_id')->count());
        $this->command->info('   Feedbacks criados             → ' . Feedback::whereNotNull('form_id')->count());
    }

    private function ensureForms(): array
    {
        $map = [];

        foreach ($this->forms as $expectedId => $data) {
            $form = Form::firstOrCreate(['name' => $data['name']], $data);
            $map[$expectedId] = $form;
            $this->command->line("  Form [{$form->id}] {$form->name}");
        }

        return $map;
    }

    private function seedElementTypes(array $formMap): void
    {
        foreach ($this->elementsByForm as $formKey => $elements) {
            $form = $formMap[$formKey] ?? null;
            if (!$form) {
                continue;
            }

            foreach ($elements as $el) {
                ElementType::updateOrCreate(
                    ['name' => $el['name']],
                    array_merge($el, ['form_id' => $form->id, 'status' => 1])
                );
            }
        }
    }

    private function seedFeedbacks(array $formMap): void
    {
        $userId     = User::orderBy('id')->value('id') ?? 1;
        $customerIds = Customer::pluck('id')->all();

        if (empty($customerIds)) {
            $this->command->warn('  Nenhum customer encontrado — feedbacks sem customer_id.');
        }

        $statuses = ['fin', 'fin', 'fin', 'pen', 'can'];

        foreach ($formMap as $form) {
            $count = rand(5, 10);
            for ($i = 0; $i < $count; $i++) {
                $status    = $statuses[array_rand($statuses)];
                $createdAt = now()->subDays(rand(1, 60));

                Feedback::create([
                    'form_id'      => $form->id,
                    'user_id'      => $userId,
                    'customer_id'  => $customerIds ? $customerIds[array_rand($customerIds)] : null,
                    'status'       => $status,
                    'content'      => fake()->paragraph(2),
                    'suggestions'  => fake()->optional(0.5)->sentence(),
                    'contact'      => fake()->name(),
                    'completed_at' => $status === 'fin' ? $createdAt->copy()->addHours(rand(1, 48)) : null,
                    'created_at'   => $createdAt,
                    'updated_at'   => $createdAt,
                ]);
            }
        }
    }
}

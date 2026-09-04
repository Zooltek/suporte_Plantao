<?php

namespace App\Repositories;

use App\Contracts\Repositories\SlaRepositoryInterface;
use App\Exceptions\SlaConfigurationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SlaRepository implements SlaRepositoryInterface
{
    public function getSettingsBySlugs(array $slugs): Collection
    {
        return DB::table('ticketit_settings')
            ->whereIn('slug', $slugs)
            ->pluck('value', 'slug');
    }

    public function updateSettings(array $data): void
    {
        DB::transaction(function () use ($data): void {
            foreach ($data as $slug => $value) {
                $updated = DB::table('ticketit_settings')
                    ->where('slug', $slug)
                    ->update(['value' => (string) $value, 'updated_at' => now()]);

                if ($updated === 0) {
                    throw new SlaConfigurationException(
                        "Configuração de SLA '{$slug}' não encontrada no banco de dados."
                    );
                }
            }
        });
    }
}

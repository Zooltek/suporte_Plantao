<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\SettingsRepositoryInterface;
use App\Models\User;
use App\Models\User\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SettingsRepository implements SettingsRepositoryInterface
{
    /**
     * Persiste os dados do usuário e sincroniza preferências em transação atômica.
     * Inclui lógica de hash de senha quando um novo valor é informado.
     */
    public function updateProfileAndPreferences(User $user, array $validatedData): void
    {
        DB::transaction(function () use ($user, $validatedData) {
            $user->email        = $validatedData['email'];
            $user->refresh_rate = $validatedData['refresh_rate'];

            if (!empty($validatedData['password'])) {
                $user->password = Hash::make($validatedData['password']);
            }

            $user->save();

            $this->syncPreferences($user->id, $validatedData);
        });
    }

    /**
     * Retorna o mapa slug => valor das preferências do usuário.
     */
    public function getForUser(int $userId): array
    {
        return Setting::where('user_id', $userId)->pluck('value', 'slug')->toArray();
    }

    /**
     * Upsert das configurações de interface do usuário.
     */
    private function syncPreferences(int $userId, array $data): void
    {
        $preferences = [
            'open_ticket_new_tab'  => data_get($data, 'open_ticket_new_tab', false),
            'show_ticket_category' => data_get($data, 'show_ticket_category', false),
        ];

        foreach ($preferences as $slug => $value) {
            Setting::updateOrCreate(
                ['user_id' => $userId, 'slug' => $slug],
                ['value' => $value ? '1' : '0', 'default' => '0']
            );
        }
    }
}

<?php

use App\Models\User;
use App\Models\User\Setting;
use App\Repositories\SettingsRepository;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function str_user(array $attrs = []): User
{
    return User::factory()->agent()->create(array_merge([
        'email'        => 'teste.' . uniqid() . '@example.com',
        'refresh_rate' => 60,
    ], $attrs));
}

// ─── updateProfileAndPreferences ──────────────────────────────────────────────

describe('SettingsRepository — updateProfileAndPreferences', function () {

    it('atualiza email e refresh_rate do usuário', function () {
        $user = str_user();
        $repo = new SettingsRepository();

        $repo->updateProfileAndPreferences($user, [
            'email'                => 'novo@example.com',
            'refresh_rate'         => 120,
            'open_ticket_new_tab'  => false,
            'show_ticket_category' => false,
        ]);

        $fresh = User::find($user->id);

        expect($fresh->email)->toBe('novo@example.com')
            ->and($fresh->refresh_rate)->toBe(120);
    });

    it('atualiza a senha quando informada', function () {
        $user = str_user();
        $repo = new SettingsRepository();

        $repo->updateProfileAndPreferences($user, [
            'email'                => $user->email,
            'refresh_rate'         => 60,
            'password'             => 'nova_senha_segura',
            'open_ticket_new_tab'  => false,
            'show_ticket_category' => false,
        ]);

        $fresh = User::find($user->id);

        expect(\Illuminate\Support\Facades\Hash::check('nova_senha_segura', $fresh->password))->toBeTrue();
    });

    it('não altera a senha quando não informada', function () {
        $user            = str_user();
        $originalHash    = $user->password;
        $repo            = new SettingsRepository();

        $repo->updateProfileAndPreferences($user, [
            'email'                => $user->email,
            'refresh_rate'         => 60,
            'open_ticket_new_tab'  => false,
            'show_ticket_category' => false,
        ]);

        $fresh = User::find($user->id);

        expect($fresh->password)->toBe($originalHash);
    });

    it('cria registros de preferência open_ticket_new_tab e show_ticket_category', function () {
        $user = str_user();
        $repo = new SettingsRepository();

        $repo->updateProfileAndPreferences($user, [
            'email'                => $user->email,
            'refresh_rate'         => 60,
            'open_ticket_new_tab'  => true,
            'show_ticket_category' => true,
        ]);

        expect(Setting::where('user_id', $user->id)->where('slug', 'open_ticket_new_tab')->value('value'))->toBe('1')
            ->and(Setting::where('user_id', $user->id)->where('slug', 'show_ticket_category')->value('value'))->toBe('1');
    });

    it('atualiza preferência existente via updateOrCreate (não duplica)', function () {
        $user = str_user();

        Setting::create(['user_id' => $user->id, 'slug' => 'open_ticket_new_tab', 'value' => '0', 'default' => '0']);

        $repo = new SettingsRepository();
        $repo->updateProfileAndPreferences($user, [
            'email'                => $user->email,
            'refresh_rate'         => 60,
            'open_ticket_new_tab'  => true,
            'show_ticket_category' => false,
        ]);

        $count = Setting::where('user_id', $user->id)->where('slug', 'open_ticket_new_tab')->count();
        $value = Setting::where('user_id', $user->id)->where('slug', 'open_ticket_new_tab')->value('value');

        expect($count)->toBe(1)
            ->and($value)->toBe('1');
    });

    it('persiste o valor 0 para preferências desativadas', function () {
        $user = str_user();
        $repo = new SettingsRepository();

        $repo->updateProfileAndPreferences($user, [
            'email'                => $user->email,
            'refresh_rate'         => 60,
            'open_ticket_new_tab'  => false,
            'show_ticket_category' => false,
        ]);

        expect(Setting::where('user_id', $user->id)->where('slug', 'open_ticket_new_tab')->value('value'))->toBe('0');
    });

});

// ─── getForUser ───────────────────────────────────────────────────────────────

describe('SettingsRepository — getForUser', function () {

    it('retorna o mapa slug => valor das preferências do usuário', function () {
        $user = str_user();

        Setting::create([
            'user_id' => $user->id,
            'slug'    => 'open_ticket_new_tab',
            'value'   => '1',
            'default' => '0',
        ]);
        Setting::create([
            'user_id' => $user->id,
            'slug'    => 'show_ticket_category',
            'value'   => '0',
            'default' => '0',
        ]);

        $repo = new SettingsRepository();

        expect($repo->getForUser($user->id))->toMatchArray([
            'open_ticket_new_tab'  => '1',
            'show_ticket_category' => '0',
        ]);
    });

    it('retorna array vazio quando o usuário não possui preferências', function () {
        $user = str_user();
        $repo = new SettingsRepository();

        expect($repo->getForUser($user->id))->toBe([]);
    });

    it('isola as preferências entre usuários distintos', function () {
        $alice = str_user();
        $bob   = str_user();

        Setting::create([
            'user_id' => $alice->id,
            'slug'    => 'open_ticket_new_tab',
            'value'   => '1',
            'default' => '0',
        ]);

        $repo = new SettingsRepository();

        expect($repo->getForUser($bob->id))->toBe([])
            ->and($repo->getForUser($alice->id))->toMatchArray(['open_ticket_new_tab' => '1']);
    });

});

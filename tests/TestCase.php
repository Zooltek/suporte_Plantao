<?php

namespace Tests;

use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\ScheduleTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Força SQLite em memória independente das variáveis de ambiente do Docker.
     * Necessário porque docker-compose injeta DB_CONNECTION=mysql no OS, que
     * sobrepõe as env vars do phpunit.xml. A solução é sobrescrever o config
     * diretamente no repositório do Laravel após o bootstrap.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        // Conexão principal: SQLite in-memory
        $app->make('config')->set('database.default', 'sqlite');
        $app->make('config')->set('database.connections.sqlite', [
            'driver'                  => 'sqlite',
            'database'                => ':memory:',
            'prefix'                  => '',
            'foreign_key_constraints' => false,
        ]);

        if (empty($app->make('config')->get('app.key'))) {
            $app->make('config')->set('app.key', 'base64:ahYw+0BFn1XkNmLL5TGdUd94qkFPWS7qQsxPJ0LsFUo=');
        }

        // Telescope usa config própria — força para sqlite também
        // (a migration usa getConnection() que lê telescope.storage.database.connection)
        $app->make('config')->set('telescope.storage.database.connection', 'sqlite');

        // Força Spatie a usar a mesma conexão sqlite
        $app->make('config')->set('permission.cache.store', 'array');

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        // Limpa cache de permissões entre testes e semeie roles/permissões no SQLite
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(ScheduleTypeSeeder::class);
    }
}

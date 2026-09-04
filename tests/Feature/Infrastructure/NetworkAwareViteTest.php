<?php

use Illuminate\Support\Facades\URL;

describe('Network-aware Vite assets', function () {
    beforeEach(function () {
        $this->hotFile = public_path('hot');

        if (is_file($this->hotFile)) {
            unlink($this->hotFile);
        }
    });

    afterEach(function () {
        URL::forceRootUrl(null);

        if (isset($this->hotFile) && is_file($this->hotFile)) {
            unlink($this->hotFile);
        }
    });

    it('mantém o Vite HMR quando o acesso é local', function () {
        file_put_contents($this->hotFile, 'http://localhost:5173');
        URL::forceRootUrl('http://localhost:8090');

        $response = $this
            ->withServerVariables([
                'HTTP_HOST' => 'localhost:8090',
                'REMOTE_ADDR' => '127.0.0.1',
            ])
            ->get('/login')
            ->assertOk();

        $response->assertSee('http://localhost:5173/@vite/client', false);

        expect($response->headers->get('Content-Security-Policy'))
            ->toContain('http://localhost:5173');
    });

    it('faz fallback para assets buildados quando o acesso vem por IP', function () {
        file_put_contents($this->hotFile, 'http://localhost:5173');
        URL::forceRootUrl('http://192.168.0.50:8090');

        $response = $this
            ->withServerVariables([
                'HTTP_HOST' => '192.168.0.50:8090',
                'REMOTE_ADDR' => '192.168.0.99',
            ])
            ->get('/login')
            ->assertOk();

        $response->assertDontSee('http://localhost:5173/@vite/client', false)
            ->assertSee('/build/', false);

        expect($response->headers->get('Content-Security-Policy'))
            ->not->toContain('http://localhost:5173');
    });
});

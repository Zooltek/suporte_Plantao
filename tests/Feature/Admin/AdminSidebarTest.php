<?php

describe('Admin sidebar', function () {
    it('mantém a seção Implantação aberta nas rotas do módulo', function () {
        actingAsAdmin();

        $response = $this->get(route('admin.implantacao.rat-modules.index'))
            ->assertOk();

        $html = $response->getContent();
        preg_match("/data-sidebar-sections='([^']+)'/", $html, $matches);

        $sections = json_decode(html_entity_decode($matches[1] ?? '', ENT_QUOTES), true, 512, JSON_THROW_ON_ERROR);

        expect($sections['implantacao'] ?? false)->toBeTrue()
            ->and($sections['gestao'] ?? true)->toBeFalse();
    });
});

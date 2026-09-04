<?php

/**
 * Garante que o CSS do tema escuro (.ocean) preserva o contraste de texto
 * em badges com fundos Tailwind claros (bg-*-100 / bg-*-50).
 *
 * Cobre o bug reportado em que badges "Pendente" e campos de agendamento
 * ficavam ilegíveis na Timeline em dark mode.
 */

beforeEach(function () {
    $this->css = file_get_contents(base_path('resources/css/app.css'));
});

it('mantém o texto escuro em badges bg-gray-100 com text-gray-700', function () {
    expect($this->css)
        ->toContain('html.ocean .bg-gray-100.text-gray-700');
});

it('cobre badges com cores semânticas comuns (amber, blue, emerald, red)', function () {
    foreach (['amber', 'blue', 'emerald', 'red'] as $color) {
        expect($this->css)
            ->toContain("html.ocean .bg-{$color}-100.text-{$color}-700")
            ->and($this->css)
            ->toContain("html.ocean .bg-{$color}-50.text-{$color}-700");
    }
});

it('define overrides de cor para text-gray-400 ao text-gray-900 em dark mode', function () {
    foreach (['400', '500', '600', '700', '800', '900'] as $weight) {
        expect($this->css)->toContain("html.ocean .text-gray-{$weight}");
    }
});

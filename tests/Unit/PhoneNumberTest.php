<?php

use App\Support\Phone\PhoneNumber;

describe('PhoneNumber::normalize', function () {
    it('remove caracteres não numéricos e trata valor nulo como vazio', function () {
        expect(PhoneNumber::normalize('(27) 99999-0000'))->toBe('27999990000')
            ->and(PhoneNumber::normalize(null))->toBe('');
    });
});

describe('PhoneNumber::variants', function () {
    it('gera variantes para número com código do país e nono dígito', function () {
        expect(PhoneNumber::variants('5527999990000'))->toBe([
            '5527999990000',
            '27999990000',
            '2799990000',
            '552799990000',
        ]);
    });

    it('gera variantes a partir de número nacional com nono dígito', function () {
        expect(PhoneNumber::variants('(27) 99999-0000'))->toBe([
            '27999990000',
            '2799990000',
            '5527999990000',
            '552799990000',
        ]);
    });

    it('inclui apenas o prefixo do país quando o número já tem 10 dígitos', function () {
        expect(PhoneNumber::variants('(27) 3333-0000'))->toBe([
            '2733330000',
            '552733330000',
        ]);
    });

    it('retorna lista vazia para entradas sem dígitos', function () {
        expect(PhoneNumber::variants(''))->toBe([])
            ->and(PhoneNumber::variants(null))->toBe([]);
    });
});

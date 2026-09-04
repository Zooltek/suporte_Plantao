import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import {
    DEFAULT_VITE_HMR_HOST,
    resolveViteHmrHost,
} from '../../vite.hmr.mjs';

describe('resolveViteHmrHost — fallback seguro para desenvolvimento local', () => {
    it('usa localhost quando VITE_HMR_HOST não foi definido', () => {
        assert.equal(resolveViteHmrHost({}), DEFAULT_VITE_HMR_HOST);
    });

    it('usa localhost quando VITE_HMR_HOST vem vazio', () => {
        assert.equal(resolveViteHmrHost({ VITE_HMR_HOST: '' }), DEFAULT_VITE_HMR_HOST);
    });

    it('ignora espaços em branco e volta para localhost', () => {
        assert.equal(resolveViteHmrHost({ VITE_HMR_HOST: '   ' }), DEFAULT_VITE_HMR_HOST);
    });
});

describe('resolveViteHmrHost — HMR compartilhado explicitamente configurado', () => {
    it('preserva um IP privado configurado para a LAN', () => {
        assert.equal(
            resolveViteHmrHost({ VITE_HMR_HOST: '192.168.0.15' }),
            '192.168.0.15',
        );
    });

    it('preserva um hostname configurado manualmente', () => {
        assert.equal(
            resolveViteHmrHost({ VITE_HMR_HOST: 'suporte-dev.local' }),
            'suporte-dev.local',
        );
    });

    it('remove espaços acidentais no valor configurado', () => {
        assert.equal(
            resolveViteHmrHost({ VITE_HMR_HOST: '  suporte-dev.local  ' }),
            'suporte-dev.local',
        );
    });
});

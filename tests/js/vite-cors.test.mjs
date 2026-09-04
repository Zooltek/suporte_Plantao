import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import { isLanOrigin, lanCorsOriginAllowlist } from '../../vite.cors.mjs';

describe('isLanOrigin — origens liberadas (LAN + localhost)', () => {
    const permitidas = [
        'http://localhost',
        'http://localhost:8090',
        'https://localhost:5173',
        'http://127.0.0.1',
        'http://127.0.0.1:8090',
        'http://192.168.0.15:8090',
        'http://192.168.255.255:5173',
        'https://192.168.1.1',
        'http://10.0.0.1',
        'http://10.255.255.254:443',
        'http://172.16.0.1',
        'http://172.31.255.254:8090',
        'http://172.20.10.5:5173',
    ];

    for (const origin of permitidas) {
        it(`libera ${origin}`, () => {
            assert.equal(isLanOrigin(origin), true);
        });
    }
});

describe('isLanOrigin — origens negadas (anti-regressão)', () => {
    const negadas = [
        // IPs públicos
        'http://8.8.8.8',
        'https://1.1.1.1:443',
        'http://203.0.113.42:8090',
        // Fora de RFC1918 em 172.x (só 172.16–172.31 é privado)
        'http://172.15.0.1',
        'http://172.32.0.1',
        'http://172.0.0.1',
        // Spoofing com hostname que começa com "localhost"
        'http://localhost.evil.com',
        'http://localhost.attacker.io:5173',
        // Spoofing de IP em subdomínio
        'http://192.168.0.15.evil.com',
        // Protocolo não HTTP
        'ftp://192.168.0.15',
        'ws://192.168.0.15:5173',
        'file:///etc/passwd',
        // Tentativa de bypass com barra extra / path
        'http://192.168.0.15:8090/@vite/client',
        'http://192.168.0.15@evil.com',
        // Valores inválidos
        '',
        'null',
        'undefined',
    ];

    for (const origin of negadas) {
        it(`nega ${origin || '(vazio)'}`, () => {
            assert.equal(isLanOrigin(origin), false);
        });
    }
});

describe('isLanOrigin — entradas não-string', () => {
    it('nega null', () => assert.equal(isLanOrigin(null), false));
    it('nega undefined', () => assert.equal(isLanOrigin(undefined), false));
    it('nega número', () => assert.equal(isLanOrigin(42), false));
    it('nega objeto', () => assert.equal(isLanOrigin({}), false));
    it('nega array', () => assert.equal(isLanOrigin([]), false));
});

describe('lanCorsOriginAllowlist — contrato estrutural', () => {
    it('é um array imutável (previne mutação acidental em runtime)', () => {
        assert.equal(Object.isFrozen(lanCorsOriginAllowlist), true);
    });

    it('contém apenas instâncias de RegExp', () => {
        for (const pattern of lanCorsOriginAllowlist) {
            assert.ok(pattern instanceof RegExp, `esperado RegExp, veio ${typeof pattern}`);
        }
    });

    it('não contém wildcard permissivo (star/match-all)', () => {
        for (const pattern of lanCorsOriginAllowlist) {
            assert.notEqual(pattern.source, '.*');
            assert.notEqual(pattern.source, '.+');
            assert.ok(
                pattern.source.startsWith('^'),
                `padrão ${pattern} deve ser ancorado em ^ para evitar match parcial`,
            );
            assert.ok(
                pattern.source.endsWith('$'),
                `padrão ${pattern} deve ser ancorado em $ para evitar match parcial`,
            );
        }
    });
});

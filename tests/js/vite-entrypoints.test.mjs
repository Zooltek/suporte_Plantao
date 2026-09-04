import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import { existsSync, readFileSync, readdirSync, statSync } from 'node:fs';
import path from 'node:path';

const repoRoot = path.resolve(import.meta.dirname, '..', '..');
const viewsRoot = path.join(repoRoot, 'resources', 'views');
const viteConfigPath = path.join(repoRoot, 'vite.config.js');

function collectFiles(dir, predicate, bucket = []) {
    for (const entry of readdirSync(dir, { withFileTypes: true })) {
        const fullPath = path.join(dir, entry.name);

        if (entry.isDirectory()) {
            collectFiles(fullPath, predicate, bucket);
            continue;
        }

        if (predicate(fullPath)) {
            bucket.push(fullPath);
        }
    }

    return bucket;
}

function extractStringLiterals(source) {
    return [...source.matchAll(/'([^']+)'|"([^"]+)"/g)]
        .map((match) => match[1] ?? match[2])
        .filter(Boolean);
}

function extractBladeViteEntries() {
    const bladeFiles = collectFiles(
        viewsRoot,
        (file) => file.endsWith('.blade.php'),
    );
    const entries = new Set();

    for (const file of bladeFiles) {
        const content = readFileSync(file, 'utf8');
        const viteCalls = content.matchAll(/@vite\(\[(.*?)\]\)/gs);

        for (const viteCall of viteCalls) {
            for (const asset of extractStringLiterals(viteCall[1])) {
                if (asset.startsWith('resources/')) {
                    entries.add(asset);
                }
            }
        }
    }

    return [...entries].sort();
}

function extractViteInputs() {
    const content = readFileSync(viteConfigPath, 'utf8');
    const inputSection = content.match(/input:\s*\[(.*?)\]/gs)?.[0] ?? '';

    return new Set(
        extractStringLiterals(inputSection).filter((asset) => asset.startsWith('resources/')),
    );
}

describe('Blade @vite entrypoints', () => {
    const bladeEntries = extractBladeViteEntries();
    const viteInputs = extractViteInputs();

    it('referenciam apenas arquivos existentes no projeto', () => {
        for (const entry of bladeEntries) {
            const fullPath = path.join(repoRoot, entry);

            assert.equal(
                existsSync(fullPath),
                true,
                `asset ausente em resources: ${entry}`,
            );
            assert.equal(
                statSync(fullPath).isFile(),
                true,
                `asset não é arquivo regular: ${entry}`,
            );
        }
    });

    it('estão cadastrados como entradas explícitas do Vite build', () => {
        for (const entry of bladeEntries) {
            assert.equal(
                viteInputs.has(entry),
                true,
                `asset usado em Blade não está em vite.config.js: ${entry}`,
            );
        }
    });
});

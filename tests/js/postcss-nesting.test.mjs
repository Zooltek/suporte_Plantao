import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import postcss from 'postcss';
import tailwindcssNesting from 'tailwindcss/nesting/index.js';
import postcssConfig from '../../postcss.config.js';

describe('postcss.config.js — contrato do dark mode legado', () => {
    it('mantém tailwindcss/nesting habilitado antes do build', () => {
        assert.equal(
            Object.hasOwn(postcssConfig.plugins, 'tailwindcss/nesting'),
            true,
        );
    });
});

describe('tailwindcss/nesting — compatibilidade com browsers sem CSS nesting nativo', () => {
    it('flatten selectors aninhados usados pelo tema ocean', async () => {
        const inputCss = `
            html.ocean {
                body,
                main,
                .bg-gray-50,
                .bg-white {
                    background-color: #0f172a !important;
                    color: #f1f5f9 !important;
                }

                .text-gray-500,
                .text-gray-600 {
                    color: #94a3b8 !important;
                }
            }
        `;

        const result = await postcss([tailwindcssNesting()]).process(inputCss, {
            from: undefined,
        });

        assert.match(
            result.css,
            /html\.ocean body,\s*html\.ocean main,\s*html\.ocean \.bg-gray-50,\s*html\.ocean \.bg-white\s*\{/s,
        );
        assert.match(
            result.css,
            /html\.ocean \.text-gray-500,\s*html\.ocean \.text-gray-600\s*\{/s,
        );
        assert.doesNotMatch(result.css, /html\.ocean\s*\{\s*body/s);
    });
});

export default {
    plugins: {
        // Transpila CSS Nesting para CSS flat em build-time.
        // Sem isso, regras como `html.ocean { body { ... } .text-gray-600 { ... } }`
        // em resources/css/app.css só aplicam em Chrome 112+/Firefox 117+/Safari 16.5+
        // — browsers legados ignoram blocos aninhados e o dark mode fica parcial.
        'tailwindcss/nesting': {},
        tailwindcss: {},
        autoprefixer: {},
    },
};

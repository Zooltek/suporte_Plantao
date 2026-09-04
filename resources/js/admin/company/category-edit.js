// resources/js/category-edit.js

document.addEventListener('DOMContentLoaded', () => {
    const btnPreVis = document.getElementById('pre-vis');
    const textArea = document.getElementById('html_textarea');

    if (!btnPreVis || !textArea) return;

    btnPreVis.addEventListener('click', () => {
        const htmlContent = textArea.value;
        
        if (!htmlContent.trim()) {
            alert("O campo HTML está vazio para pré-visualização.");
            return;
        }

        const width = 1000;
        const height = 600;
        
        // Uso de globalThis para atender à regra S7764
        const left = (globalThis.screen.width / 2) - (width / 2);
        const top = (globalThis.screen.height / 2) - (height / 2);

        const win = globalThis.open(
            "", 
            "_blank", 
            `width=${width},height=${height},top=${top},left=${left},scrollbars=yes,resizable=yes`
        );

        if (win) {
            /**
             * CORREÇÃO S1874: Substituindo document.write() por manipulação direta do DOM.
             * Criamos a estrutura básica e injetamos o conteúdo de forma segura.
             */
            const doc = win.document;
            doc.title = "Pré-visualização HTML";
            
            // Definindo o HTML base de forma segura
            doc.documentElement.innerHTML = `
                <head>
                    <meta charset="UTF-8">
                    <title>Pré-visualização HTML</title>
                    <style>
                        body { font-family: sans-serif; padding: 20px; line-height: 1.6; color: #333; }
                        hr { border: 0; border-top: 1px solid #eee; }
                    </style>
                </head>
                <body>
                    <div id="preview-container"></div>
                </body>
            `;

            // Injetando o conteúdo do usuário no container específico
            const container = doc.getElementById('preview-container');
            if (container) {
                container.innerHTML = htmlContent;
            }
        } else {
            alert("O bloqueador de pop-ups impediu a pré-visualização. Por favor, autorize pop-ups para este site.");
        }
    });
});


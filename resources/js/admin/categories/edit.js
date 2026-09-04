/**
 * Category Edit Form - Alpine.js Component
 */

window.categoryEditForm = function () {
    return {
        htmlContent: '',
        submitting: false,

        /**
         * Preview HTML content
         */
        previewHTML() {
            if (!this.htmlContent || this.htmlContent.trim() === '') {
                alert('⚠️ Digite algum HTML primeiro!');
                return;
            }

            const win = window.open("", "_blank", "toolbar=no,scrollbars=yes,resizable=yes,width=900,height=700");
            win.document.write(`
                <!DOCTYPE html>
                <html lang="pt-BR">
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <title>Preview HTML - Categoria</title>
                        <style>
                            * { margin: 0; padding: 0; box-sizing: border-box; }
                            body { 
                                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
                                padding: 20px; 
                                background: # f9fafb;
                            }
                        </style>
                    </head>
                    <body>
                        ${this.htmlContent}
                    </body>
                </html>
            `);
            win.document.close();
        }
    };
};

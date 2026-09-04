// resources/js/index.js

document.addEventListener('DOMContentLoaded', () => {
    const btnSave = document.getElementById('btn-save-feedback');
    const form = document.getElementById('modal-form');
    const config = globalThis.FeedbackConfig;

    if (!btnSave || !form) return;

    btnSave.addEventListener('click', async () => {
        // desabilita o botão para evitar cliques duplos
        btnSave.disabled = true;
        btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';

        // coleta os dados do formulário
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            await globalThis.axios.post(config.storeUrl, data);
            
            // sucesso: Recarrega a página
            globalThis.location.reload();
            
        } catch (error) {
            console.error('Erro ao salvar feedback:', error);
            
            const errorMsg = error.response?.data?.message || 'Erro ao processar requisição';
            alert('Erro: ' + errorMsg);
            
            btnSave.disabled = false;
            btnSave.innerHTML = 'Salvar';
        }
    });
});
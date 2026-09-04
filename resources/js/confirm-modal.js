/**
 * confirmModal — substitui window.confirm() por um modal Alpine.js.
 *
 * @param {object} options
 * @param {string} [options.title]         Título do modal
 * @param {string} [options.message]       Mensagem de confirmação
 * @param {string} [options.confirmLabel]  Label do botão de confirmação (padrão: 'Confirmar')
 * @param {string} [options.cancelLabel]   Label do botão de cancelar  (padrão: 'Cancelar')
 * @returns {Promise<boolean>}
 */
window.confirmModal = function (options = {}) {
    return new Promise((resolve) => {
        window.dispatchEvent(
            new CustomEvent('confirm-modal:open', {
                detail: { ...options, resolve },
            })
        );
    });
};

/**
 * confirmDelete(ticketId) — helper para a view condensed.blade.php.
 * Envia DELETE via form dinâmico após confirmação no modal.
 */
window.confirmDelete = async function (ticketId) {
    const ok = await window.confirmModal({
        title: 'Excluir chamado?',
        message: `O chamado #${ticketId} será excluído permanentemente. Esta ação não pode ser desfeita.`,
        confirmLabel: 'Excluir',
    });
    if (!ok) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/support/tickets/${ticketId}`;
    form.innerHTML = `<input type="hidden" name="_token" value="${csrf}">
                      <input type="hidden" name="_method" value="DELETE">`;
    document.body.appendChild(form);
    form.submit();
};

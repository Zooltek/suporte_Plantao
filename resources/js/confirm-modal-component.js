/**
 * confirmModal — componente Alpine para o modal de confirmação global.
 *
 * Compatível com @alpinejs/csp (sem unsafe-eval).
 * Registrado via Alpine.data('confirmModal', confirmModal) em app.js.
 */
export function confirmModal() {
    return {
        isOpen: false,
        title: '',
        message: '',
        confirmLabel: 'Confirmar',
        cancelLabel: 'Cancelar',
        _resolve: null,

        open(detail) {
            this.title        = detail.title        ?? 'Confirmar ação';
            this.message      = detail.message      ?? 'Esta ação não pode ser desfeita.';
            this.confirmLabel = detail.confirmLabel ?? 'Confirmar';
            this.cancelLabel  = detail.cancelLabel  ?? 'Cancelar';
            this._resolve     = detail.resolve;
            this.isOpen       = true;
            this.$nextTick(() => this.$refs.cancelBtn?.focus());
        },

        confirm() {
            this._resolve?.(true);
            this.isOpen = false;
        },

        cancel() {
            this._resolve?.(false);
            this.isOpen = false;
        },
    };
}

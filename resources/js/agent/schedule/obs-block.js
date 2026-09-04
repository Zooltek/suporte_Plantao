export function obsBlock() {
    return {
        value: '',
        expanded: false,

        init() {
            this.value = this.$el.dataset.obs || '';
            this.$nextTick(() => {
                if (this.$refs.inline) this.autoResize(this.$refs.inline);
            });
        },

        autoResize(el) {
            el.style.height = 'auto';
            el.style.height = Math.max(el.scrollHeight, 120) + 'px';
        },

        openModal() {
            this.expanded = true;
            this.$nextTick(() => { this.$refs.modal.focus(); });
        },

        closeModal() {
            this.expanded = false;
            this.$nextTick(() => { this.autoResize(this.$refs.inline); });
        },
    };
}

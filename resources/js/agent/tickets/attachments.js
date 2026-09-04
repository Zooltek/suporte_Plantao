/**
 * Alpine.js component para gerenciamento de anexos de um ticket.
 *
 * Arquivo separado da view Blade (SRP + convenção do projeto).
 * Registrado via: Alpine.data('ticketAttachments', ticketAttachments)
 *
 * Funcionalidades:
 *  - Lista de anexos via API
 *  - Upload de qualquer extensão (até 50MB)
 *  - Visualização direta no browser (imagens, PDFs, vídeos, txt)
 *  - Download para extensões não visualizáveis
 *  - Exclusão com confirmação
 */
export function ticketAttachments(ticketId) {
    return {
        ticketId,
        attachments: [],
        loading:     true,
        uploading:   false,
        uploadError: null,

        // ── Preview (modal) ───────────────────────────────────────────────
        previewItem: null,

        init() {
            this.loadData();
        },

        loadData() {
            this.loading = true;
            fetch(`/api/v1/tickets/${this.ticketId}/attachments`, {
                headers: { 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(json => {
                this.attachments = json.data || [];
                this.loading     = false;
            })
            .catch(() => {
                this.loading = false;
            });
        },

        // ── Upload ────────────────────────────────────────────────────────
        handleFileInput(event) {
            const files = Array.from(event.target.files);
            files.forEach(f => this.uploadFile(f));
            event.target.value = '';
        },

        handleDrop(event) {
            const files = Array.from(event.dataTransfer.files);
            files.forEach(f => this.uploadFile(f));
        },

        uploadFile(file) {
            this.uploading   = true;
            this.uploadError = null;

            const formData = new FormData();
            formData.append('file', file);
            formData.append('ticket_id', this.ticketId);

            fetch('/api/v1/attachments', {
                method: 'POST',
                headers: {
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: formData,
            })
            .then(r => r.json().then(json => ({ ok: r.ok, json })))
            .then(({ ok, json }) => {
                if (!ok) throw new Error(json.message || 'Upload falhou.');
                this.attachments.unshift(json.data);
                this.uploading = false;
            })
            .catch(err => {
                this.uploadError = err.message || 'Erro ao enviar arquivo.';
                this.uploading   = false;
            });
        },

        // ── Exclusão ──────────────────────────────────────────────────────
        deleteAttachment(id) {
            if (!confirm('Remover este anexo permanentemente?')) return;

            fetch(`/api/v1/attachments/${id}`, {
                method:  'DELETE',
                headers: {
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            })
            .then(r => {
                if (!r.ok) throw new Error('Erro ao remover anexo.');
                this.attachments = this.attachments.filter(a => a.id !== id);
                if (this.previewItem?.id === id) this.previewItem = null;
            })
            .catch(err => alert(err.message || 'Erro ao remover anexo.'));
        },

        // ── Preview ───────────────────────────────────────────────────────
        openPreview(item) {
            if (item.viewable) {
                this.previewItem = item;
            } else {
                window.open(`/api/v1/attachments/${item.id}/view`, '_blank');
            }
        },

        closePreview() {
            this.previewItem = null;
        },

        // ── Utilitários ───────────────────────────────────────────────────
        extensionIcon(mime) {
            const icons = {
                pdf: '📄', jpg: '🖼️', jpeg: '🖼️', png: '🖼️', gif: '🖼️',
                webp: '🖼️', svg: '🖼️', mp4: '🎬', webm: '🎬', ogg: '🎬',
                xls: '📊', xlsx: '📊', csv: '📊', doc: '📝', docx: '📝',
                txt: '📃', zip: '🗜️', rar: '🗜️', '7z': '🗜️', tar: '🗜️',
            };
            return icons[mime?.toLowerCase()] || '📎';
        },

        formatDate(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleString('pt-BR', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit',
            });
        },
    };
}

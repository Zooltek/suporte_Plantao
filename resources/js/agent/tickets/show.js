/**
 * Alpine.js component para a tela de detalhe de um ticket.
 *
 * Arquivo separado da view Blade (SRP + convenção do projeto).
 * Registrado via: Alpine.data('ticketShow', ticketShow)
 *
 * Funcionalidades:
 *  - Tab bar responsiva (Comentários / Atendimentos / Problemas / Anexos / Histórico)
 *  - Formulário de comentário com prévia de caracteres
 *  - Fluxo explícito de encerramento de ticket
 *  - Scroll automático para o último comentário na primeira carga
 *  - Botão EasyWiki (modal de confirmação antes de redirecionar)
 */
import { setupWhatsAppEmojiPicker } from './whatsapp-emoji-picker.js';

export function ticketShow(config = {}) {
    const locationSearch = typeof window !== 'undefined'
        ? window.location.search
        : '';

    return {
        // ── Tabs ─────────────────────────────────────────────────────────
        activeTab: (new URLSearchParams(locationSearch)).get('tab') || 'comments',

        setTab(tab) {
            this.activeTab = tab;
        },

        // ── Comentário ───────────────────────────────────────────────────
        commentBody: '',
        isSubmitting: false,
        commentCharLimit: 5000,

        get commentLength() {
            return this.commentBody.length;
        },

        get commentTooLong() {
            return this.commentLength > this.commentCharLimit;
        },

        // ── Confirmação de ações destrutivas ─────────────────────────────
        confirmClose:  false,
        confirmDelete: false,
        closeStatuses: Array.isArray(config.closeStatuses) ? config.closeStatuses : [],
        closeStatusId: String(config.initialCloseStatusId || ''),
        closeSolution: String(config.initialCloseSolution || ''),
        openCloseOnLoad: Boolean(config.openCloseOnLoad),

        get selectedCloseStatus() {
            return this.closeStatuses.find(status => String(status.id) === String(this.closeStatusId)) || null;
        },

        get closeRequiresSolution() {
            return Boolean(this.selectedCloseStatus?.requiresSolution);
        },

        get closeHelperText() {
            if (!this.selectedCloseStatus) {
                return 'Selecione como deseja encerrar este chamado.';
            }

            if (this.closeRequiresSolution) {
                return 'Informe a solução aplicada para encerrar como Resolvido.';
            }

            return 'O chamado será encerrado sem exigir descrição de solução e você retornará para a fila de pendências.';
        },

        get closeSubmitLabel() {
            if (!this.selectedCloseStatus) {
                return 'Confirmar fechamento';
            }

            return `Confirmar ${this.selectedCloseStatus.name} e ir para pendências`;
        },

        openClosePanel() {
            this.confirmClose = true;
        },

        // ── Scroll para âncora ───────────────────────────────────────────
        init() {
            const initializeState = () => {
                this.confirmClose = this.openCloseOnLoad;
                this.initWhatsAppChat();

                if (typeof window !== 'undefined' && window.location.hash) {
                    const el = document.querySelector(window.location.hash);
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            };

            if (typeof this.$nextTick === 'function') {
                this.$nextTick(initializeState);

                return;
            }

            initializeState();
        },

        // ── WhatsApp ─────────────────────────────────────────────────────
        initWhatsAppChat() {
            if (typeof document === 'undefined') return;

            const list = document.getElementById('whatsapp-message-list');
            const form = document.getElementById('whatsapp-message-form');
            if (!list) return;

            const csrf = list.dataset.csrf || document.querySelector('meta[name="csrf-token"]')?.content || '';
            const messageIds = new Set(
                Array.from(list.querySelectorAll('[data-whatsapp-message-id]'))
                    .map((el) => Number(el.dataset.whatsappMessageId))
                    .filter(Boolean)
            );

            const lastId = () => Math.max(0, ...Array.from(messageIds));
            const nearBottom = () => list.scrollHeight - list.scrollTop - list.clientHeight < 96;
            const scrollBottom = () => { list.scrollTop = list.scrollHeight; };

            const escapeHtml = (value = '') => String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const renderMessage = (message) => {
                if (!message?.id || messageIds.has(Number(message.id))) return;

                const inbound = message.direction === 'inbound';
                const internal = Boolean(message.is_internal);
                const deleted = Boolean(message.is_deleted);
                const bubbleClass = internal
                    ? 'bg-amber-50 text-amber-900 rounded-tr-none border border-amber-200'
                    : (inbound
                        ? 'bg-white text-gray-800 rounded-tl-none border border-gray-200'
                        : 'bg-green-500 text-white rounded-tr-none');
                const alignClass = inbound ? 'justify-start' : 'justify-end';
                const ownerLabel = internal
                    ? 'Interna'
                    : (!inbound ? escapeHtml(message.user_name || 'Bot') : '');
                const body = deleted
                    ? '<p class="whitespace-pre-wrap break-words italic opacity-75">Mensagem excluída.</p>'
                    : this.renderWhatsAppBody(message, escapeHtml);
                const deleteUrl = (list.dataset.deleteUrlTemplate || '').replace('__MESSAGE_ID__', message.id);
                const updateUrl = (list.dataset.updateUrlTemplate || '').replace('__MESSAGE_ID__', message.id);
                const canUpdate = list.dataset.canUpdate === '1';
                const deleteButton = deleted || !deleteUrl || !canUpdate
                    ? ''
                    : `<button type="button" data-whatsapp-delete="${message.id}" class="text-[10px] font-semibold text-gray-400 hover:text-red-600">Excluir</button>`;
                const editButton = deleted || !updateUrl || !canUpdate || inbound
                    ? ''
                    : `<button type="button" data-whatsapp-edit="${message.id}" class="text-[10px] font-semibold text-gray-400 hover:text-blue-600 ml-2">Editar</button>`;
                const wasBottom = nearBottom();

                list.insertAdjacentHTML('beforeend', `
                    <div data-whatsapp-message-id="${message.id}" class="flex ${alignClass}">
                        <div class="max-w-[75%] ${inbound ? '' : 'order-last'}">
                            <div class="rounded-2xl px-3.5 py-2.5 text-sm shadow-sm ${bubbleClass}">
                                ${body}
                            </div>
                            <div class="mt-1 flex items-center gap-2 ${inbound ? 'justify-start' : 'justify-end'}">
                                <p class="text-[10px] text-gray-400">
                                    ${escapeHtml(message.created_at_label || '')}${ownerLabel ? ` <span class="ml-1">· ${ownerLabel}</span>` : ''}
                                </p>
                                ${deleteButton}
                                ${editButton}
                            </div>
                        </div>
                    </div>
                `);

                messageIds.add(Number(message.id));
                if (wasBottom) scrollBottom();
            };

            const poll = async () => {
                if (!list.dataset.pollUrl) return;

                try {
                    const url = new URL(list.dataset.pollUrl, window.location.origin);
                    url.searchParams.set('after_id', String(lastId()));
                    const response = await fetch(url, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) return;
                    const payload = await response.json();
                    (payload.messages || []).forEach(renderMessage);
                } catch {
                    // Polling deve ser silencioso; erros momentâneos não podem travar a tela.
                }
            };

            list.addEventListener('click', async (event) => {
                const deleteTarget = event.target.closest('[data-whatsapp-delete]');
                if (deleteTarget) {
                    const id = deleteTarget.dataset.whatsappDelete;
                    const url = (list.dataset.deleteUrlTemplate || '').replace('__MESSAGE_ID__', id);
                    if (!url) return;

                    const response = await fetch(url, {
                        method: 'DELETE',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                        credentials: 'same-origin',
                    });

                    if (response.ok) {
                        const row = list.querySelector(`[data-whatsapp-message-id="${id}"]`);
                        const bubble = row?.querySelector('.rounded-2xl');
                        if (bubble) bubble.innerHTML = '<p class="whitespace-pre-wrap break-words italic opacity-75">Mensagem excluída.</p>';
                        deleteTarget.remove();
                        const editBtn = list.querySelector(`[data-whatsapp-edit="${id}"]`);
                        if (editBtn) editBtn.remove();
                    }
                    return;
                }

                const editTarget = event.target.closest('[data-whatsapp-edit]');
                if (editTarget) {
                    const id = editTarget.dataset.whatsappEdit;
                    const row = list.querySelector(`[data-whatsapp-message-id="${id}"]`);
                    const bubble = row?.querySelector('.rounded-2xl');
                    if (!bubble) return;

                    const currentText = bubble.querySelector('p')?.textContent || '';
                    openWhatsAppEditModal(id, currentText);
                    return;
                }
            });

            window.openWhatsAppEditModal = function(id, currentText) {
                const modal = document.getElementById('whatsapp-edit-modal');
                const textarea = document.getElementById('whatsapp-edit-text');
                const saveBtn = document.getElementById('whatsapp-edit-save');

                if (!modal || !textarea) return;

                textarea.value = currentText;
                modal.classList.remove('hidden');

                const handleSave = async () => {
                    const newText = textarea.value.trim();
                    if (!newText || newText === currentText) {
                        closeWhatsAppEditModal();
                        return;
                    }

                    saveBtn.disabled = true;
                    saveBtn.textContent = 'Salvando...';

                    const baseUrl = list.dataset.sendUrl?.split('/messages')[0] || '';
                    const updateUrl = `${baseUrl}/messages/${id}`;

                    try {
                        const response = await fetch(updateUrl, {
                            method: 'PUT',
                            headers: {
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ body: newText }),
                            credentials: 'same-origin',
                        });

                        if (response.ok) {
                            const payload = await response.json();
                            const row = list.querySelector(`[data-whatsapp-message-id="${id}"]`);
                            const bubble = row?.querySelector('.rounded-2xl');
                            if (bubble && payload.message?.body) {
                                bubble.innerHTML = `<p class="whitespace-pre-wrap break-words">${escapeHtml(payload.message.body)}</p>`;
                            }
                            closeWhatsAppEditModal();
                        } else {
                            alert('Erro ao editar mensagem. Tente novamente.');
                        }
                    } catch (e) {
                        alert('Erro ao editar mensagem.');
                    } finally {
                        saveBtn.disabled = false;
                        saveBtn.textContent = 'Salvar Alterações';
                    }
                };

                saveBtn.onclick = handleSave;
                textarea.onkeydown = (e) => {
                    if (e.key === 'Enter' && e.ctrlKey) handleSave();
                };
            };

            window.closeWhatsAppEditModal = function() {
                const modal = document.getElementById('whatsapp-edit-modal');
                if (modal) modal.classList.add('hidden');
            };

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeWhatsAppEditModal();
            });

            if (form) {
                const textarea = form.querySelector('textarea[name="message"]');
                const emojiButton = form.querySelector('[data-whatsapp-emoji]');
                const recordButton = form.querySelector('[data-whatsapp-record]');
                const recordingLabel = form.querySelector('[data-whatsapp-recording]');
                const attachmentBtn = document.getElementById('whatsapp-attachment-btn');
                const attachmentInput = document.getElementById('whatsapp-attachment');
                const attachmentPreview = document.getElementById('whatsapp-attachment-preview');
                const attachmentName = document.getElementById('whatsapp-attachment-name');
                const attachmentClear = document.getElementById('whatsapp-attachment-clear');

                attachmentBtn?.addEventListener('click', () => {
                    attachmentInput?.click();
                });

                attachmentInput?.addEventListener('change', () => {
                    const file = attachmentInput.files[0];
                    if (file) {
                        attachmentName.textContent = file.name;
                        attachmentPreview?.classList.remove('hidden');
                    }
                });

                attachmentClear?.addEventListener('click', () => {
                    attachmentInput.value = '';
                    attachmentPreview?.classList.add('hidden');
                });

                if (emojiButton && textarea) {
                    setupWhatsAppEmojiPicker(emojiButton, textarea);
                }

                textarea?.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' || event.shiftKey || event.isComposing) return;
                    event.preventDefault();
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                    }
                });

                // Colar imagem (Ctrl+V / Cmd+V) — prints da tela viram anexo
                textarea?.addEventListener('paste', (event) => {
                    if (!event.clipboardData || !attachmentInput) return;

                    const items = Array.from(event.clipboardData.items || []);
                    const imageItem = items.find(
                        (item) => item.kind === 'file' && typeof item.type === 'string' && item.type.startsWith('image/')
                    );

                    if (!imageItem) return; // sem imagem no clipboard → mantém paste de texto

                    const original = imageItem.getAsFile();
                    if (!original) return;

                    event.preventDefault();

                    const extension = (original.type.split('/')[1] || 'png').toLowerCase();
                    const stamp = new Date().toISOString().replace(/[-:.TZ]/g, '').slice(0, 14);
                    const renamed = new File(
                        [original],
                        `print-${stamp}.${extension}`,
                        { type: original.type }
                    );

                    const transfer = new DataTransfer();
                    transfer.items.add(renamed);
                    attachmentInput.files = transfer.files;
                    attachmentInput.dispatchEvent(new Event('change', { bubbles: true }));
                });

                const recordingBar = document.getElementById('whatsapp-recording-bar');
                const recordingTimer = document.getElementById('whatsapp-recording-timer');
                const recordingCancelBtn = document.getElementById('whatsapp-recording-cancel');
                const recordingStopBtn = document.getElementById('whatsapp-recording-stop');
                const audioPreview = document.getElementById('whatsapp-audio-preview');
                const audioPlayer = document.getElementById('whatsapp-audio-player');
                const audioPreviewDuration = document.getElementById('whatsapp-audio-preview-duration');
                const audioDiscardBtn = document.getElementById('whatsapp-audio-discard');
                const audioSendBtn = document.getElementById('whatsapp-audio-send');

                let recorder = null;
                let audioChunks = [];
                let recordingTimerInterval = null;
                let recordingSeconds = 0;
                let isCancellingRecord = false;
                let pendingAudio = null; // { blob, extension, url }

                const formatTime = (totalSeconds) => {
                    const minutes = Math.floor(totalSeconds / 60);
                    const seconds = totalSeconds % 60;
                    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                };

                const clearPendingAudio = () => {
                    if (pendingAudio?.url) {
                        try { URL.revokeObjectURL(pendingAudio.url); } catch (_) {}
                    }
                    pendingAudio = null;
                    if (audioPlayer) {
                        audioPlayer.pause();
                        audioPlayer.removeAttribute('src');
                        audioPlayer.load();
                    }
                    audioPreview?.classList.add('hidden');
                };

                audioDiscardBtn?.addEventListener('click', () => {
                    clearPendingAudio();
                });

                audioSendBtn?.addEventListener('click', async () => {
                    if (!pendingAudio || !list?.dataset?.sendUrl) return;

                    const originalBtnHtml = audioSendBtn.innerHTML;
                    audioSendBtn.disabled = true;
                    audioSendBtn.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg> Enviando...
                    `;

                    const data = new FormData();
                    data.append('audio', pendingAudio.blob, `audio.${pendingAudio.extension}`);
                    data.append('_token', csrf);
                    const isInternal = form.querySelector('input[name="internal"]')?.checked;
                    if (isInternal) {
                        data.append('internal', '1');
                    }

                    try {
                        const response = await fetch(list.dataset.sendUrl, {
                            method: 'POST',
                            body: data,
                            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                            credentials: 'same-origin',
                        });

                        if (response.ok) {
                            const payload = await response.json();
                            renderMessage(payload.message);
                            clearPendingAudio();
                        } else {
                            const errorData = await response.json().catch(() => ({}));
                            alert('Erro ao enviar áudio: ' + (errorData.message || `HTTP ${response.status}`));
                        }
                    } catch (e) {
                        alert('Falha de rede ao enviar áudio: ' + e.message);
                    } finally {
                        audioSendBtn.disabled = false;
                        audioSendBtn.innerHTML = originalBtnHtml;
                    }
                });

                recordingCancelBtn?.addEventListener('click', () => {
                    if (recorder?.state === 'recording') {
                        isCancellingRecord = true;
                        recorder.stop();
                    }
                });

                recordingStopBtn?.addEventListener('click', () => {
                    if (recorder?.state === 'recording') {
                        recorder.stop();
                    }
                });

                recordButton?.addEventListener('click', async () => {
                    const isSecure = window.isSecureContext
                        || ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname);

                    if (!isSecure) {
                        alert(
                            'Gravação de áudio requer conexão segura (HTTPS).\n\n' +
                            'O sistema está sendo acessado por HTTP, e os navegadores bloqueiam o microfone fora de HTTPS ou localhost.\n\n' +
                            'Acesse o sistema por HTTPS ou pelo endereço localhost para usar este recurso.'
                        );
                        return;
                    }

                    if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
                        alert(
                            'Seu navegador não oferece suporte à gravação de áudio.\n\n' +
                            'Atualize para a versão mais recente do Chrome, Edge ou Firefox e tente novamente.'
                        );
                        return;
                    }

                    if (recorder?.state === 'recording') {
                        recorder.stop();
                        return;
                    }

                    clearPendingAudio();

                    try {
                        // Constraints para melhor qualidade: mono, 48kHz e filtros de áudio
                        const stream = await navigator.mediaDevices.getUserMedia({
                            audio: {
                                channelCount: 1,
                                sampleRate: 48000,
                                echoCancellation: true,
                                noiseSuppression: true,
                                autoGainControl: true,
                            },
                        });

                        audioChunks = [];
                        isCancellingRecord = false;

                        // Seleciona o melhor codec disponível (opus → webm → padrão)
                        const candidates = [
                            'audio/webm;codecs=opus',
                            'audio/ogg;codecs=opus',
                            'audio/webm',
                            'audio/mp4',
                        ];
                        const mimeType = candidates.find((type) => MediaRecorder.isTypeSupported(type)) || '';
                        const options = mimeType ? { mimeType, audioBitsPerSecond: 128000 } : { audioBitsPerSecond: 128000 };

                        recorder = new MediaRecorder(stream, options);

                        // Visualização de volume usa AudioContext em paralelo
                        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                        const source = audioContext.createMediaStreamSource(stream);
                        const analyser = audioContext.createAnalyser();
                        analyser.fftSize = 256;
                        source.connect(analyser);

                        const volumeDisplay = document.getElementById('whatsapp-volume-level');
                        const dataArray = new Uint8Array(analyser.frequencyBinCount);
                        const updateVolume = () => {
                            if (recorder?.state !== 'recording') return;
                            analyser.getByteFrequencyData(dataArray);
                            const sum = dataArray.reduce((a, b) => a + b, 0);
                            const average = sum / dataArray.length;
                            const percent = Math.min(100, Math.round(average * 1.5));
                            if (volumeDisplay) volumeDisplay.style.width = percent + '%';
                            requestAnimationFrame(updateVolume);
                        };

                        recordingSeconds = 0;
                        if (recordingTimer) recordingTimer.textContent = '00:00';
                        recordingBar?.classList.remove('hidden');
                        recordingLabel?.classList.remove('hidden');

                        recordingTimerInterval = setInterval(() => {
                            recordingSeconds++;
                            if (recordingTimer) {
                                recordingTimer.textContent = formatTime(recordingSeconds);
                            }
                        }, 1000);

                        recorder.ondataavailable = (event) => {
                            if (event.data && event.data.size > 0) audioChunks.push(event.data);
                        };
                        recorder.onstart = () => updateVolume();
                        recorder.onstop = () => {
                            if (recordingTimerInterval) {
                                clearInterval(recordingTimerInterval);
                                recordingTimerInterval = null;
                            }
                            stream.getTracks().forEach((track) => track.stop());
                            audioContext.close().catch(() => {});
                            recordingBar?.classList.add('hidden');
                            recordingLabel?.classList.add('hidden');
                            if (volumeDisplay) volumeDisplay.style.width = '0%';

                            if (isCancellingRecord) {
                                isCancellingRecord = false;
                                audioChunks = [];
                                return;
                            }

                            if (audioChunks.length === 0) return;

                            const blobType = mimeType || 'audio/webm';
                            const audioBlob = new Blob(audioChunks, { type: blobType });
                            const extension = blobType.includes('ogg') ? 'ogg' : (blobType.includes('mp4') ? 'm4a' : 'webm');

                            if (audioBlob.size < 1000) {
                                alert('Áudio muito curto ou microfone sem captura. Tente novamente.');
                                return;
                            }

                            const audioUrl = URL.createObjectURL(audioBlob);
                            pendingAudio = { blob: audioBlob, extension, url: audioUrl };

                            if (audioPlayer) {
                                audioPlayer.src = audioUrl;
                                audioPlayer.onloadedmetadata = () => {
                                    if (audioPreviewDuration && audioPlayer.duration && !isNaN(audioPlayer.duration) && isFinite(audioPlayer.duration)) {
                                        audioPreviewDuration.textContent = formatTime(Math.round(audioPlayer.duration));
                                    } else if (audioPreviewDuration) {
                                        audioPreviewDuration.textContent = formatTime(recordingSeconds);
                                    }
                                };
                            }

                            audioPreview?.classList.remove('hidden');
                        };
                        recorder.onerror = (event) => {
                            console.error('[WhatsApp] Erro na gravação:', event.error);
                            if (recordingTimerInterval) {
                                clearInterval(recordingTimerInterval);
                                recordingTimerInterval = null;
                            }
                            stream.getTracks().forEach((track) => track.stop());
                            audioContext.close().catch(() => {});
                            recordingBar?.classList.add('hidden');
                            recordingLabel?.classList.add('hidden');
                            alert('Erro ao gravar áudio. Verifique as permissões do navegador.');
                        };

                        recorder.start(500);
                    } catch (error) {
                        console.error('[WhatsApp] Erro ao acessar microfone:', error);
                        if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                            alert('Permissão de microfone negada. Permita o acesso ao microfone nas configurações do navegador.');
                        } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                            alert('Nenhum microfone encontrado. Conecte um microfone e tente novamente.');
                        } else {
                            alert('Erro ao acessar o microfone: ' + (error.message || error.name));
                        }
                    }
                });

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const data = new FormData(form);

                    const attachmentFile = data.get('attachment');
                    const messageText = data.get('message');

                    console.log('[WhatsApp] Enviando:', {
                        hasAttachment: !!attachmentFile,
                        attachmentName: attachmentFile?.name,
                        attachmentType: attachmentFile?.type,
                        hasMessage: !!messageText,
                        sendUrl: list.dataset.sendUrl,
                    });

                    if (!list.dataset.sendUrl) {
                        console.error('[WhatsApp] URL de envio não encontrada');
                        alert('Erro: URL de envio não encontrada');
                        return;
                    }

                    const response = await fetch(list.dataset.sendUrl, {
                        method: 'POST',
                        body: data,
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        try {
                            const errorData = await response.json();
                            console.error('[WhatsApp] Erro ao enviar mensagem:', errorData);
                            alert('Erro: ' + (errorData.message || errorData.errors ? JSON.stringify(errorData.errors) : 'Erro desconhecido'));
                        } catch (e) {
                            const text = await response.text();
                            console.error('[WhatsApp] Erro não JSON:', text);
                            alert('Erro ao enviar mensagem. Status: ' + response.status);
                        }
                        return;
                    }
                    const payload = await response.json();
                    renderMessage(payload.message);
                    form.reset();
                    // Limpar preview de attachment
                    const attachmentPreview = document.getElementById('whatsapp-attachment-preview');
                    if (attachmentPreview) attachmentPreview.classList.add('hidden');
                });
            }

            scrollBottom();
            window.setInterval(poll, 4000);
        },

        renderWhatsAppBody(message, escapeHtml) {
            const bodyText = message.body ? escapeHtml(message.body) : '';
            const downloadHref = message.download_url || message.attachment_url;
            const inlineHref = message.attachment_url || message.download_url;
            const fileName = escapeHtml(message.original_filename || 'arquivo');
            const mime = (message.mime_type || '').toLowerCase();

            // Mensagem de Contato / vCard
            if (message.type === 'contact' || mime.includes('vcard')) {
                let displayName = fileName || 'Contato';
                let phone = '';
                let vcardData = message.body || '';

                try {
                    if (message.body && message.body.startsWith('{')) {
                        const parsed = JSON.parse(message.body);
                        if (parsed.displayName) displayName = parsed.displayName;
                        if (parsed.vcard) vcardData = parsed.vcard;
                    }
                } catch (e) {}

                // Extrai telefone do vCard se houver
                const telMatch = vcardData.match(/TEL[^\:]*:(.+)/i);
                if (telMatch) {
                    phone = telMatch[1].replace(/\r|\n/g, '').trim();
                }

                const vcfDataUri = 'data:text/vcard;charset=utf-8,' + encodeURIComponent(vcardData || `BEGIN:VCARD\nVERSION:3.0\nFN:${displayName}\nTEL:${phone}\nEND:VCARD`);

                return `<div class="p-3 bg-white/90 rounded-xl border border-gray-200 shadow-sm text-xs space-y-2 max-w-[280px]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm">
                            👤
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-gray-900 truncate">${escapeHtml(displayName)}</p>
                            ${phone ? `<p class="text-gray-500 text-[11px] truncate">${escapeHtml(phone)}</p>` : '<p class="text-gray-400 text-[11px]">Cartão de contato</p>'}
                        </div>
                    </div>
                    <div class="pt-2 border-t border-gray-100 flex items-center justify-between">
                        <a href="${vcfDataUri}" download="${displayName}.vcf"
                           class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 hover:text-emerald-800 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Salvar Contato
                        </a>
                        ${phone ? `<a href="https://wa.me/${phone.replace(/\D/g, '')}" target="_blank" class="text-[11px] font-bold text-indigo-600 hover:underline">Conversar</a>` : ''}
                    </div>
                </div>`;
            }

            // Mensagem de Imagem
            if (message.type === 'image' && inlineHref) {
                const url = escapeHtml(inlineHref);
                const dlUrl = escapeHtml(downloadHref || url);
                return `<div class="space-y-1.5">
                    <a href="${url}" target="_blank" class="block group relative overflow-hidden rounded-xl border border-gray-200 shadow-sm max-w-[260px]">
                        <img src="${url}" alt="Imagem" class="w-full max-h-[260px] object-contain bg-black/5 group-hover:scale-105 transition-transform duration-200">
                    </a>
                    ${bodyText ? `<p class="text-xs whitespace-pre-wrap break-words mt-1">${bodyText}</p>` : ''}
                    <div class="flex items-center justify-between text-[11px] pt-1">
                        <span class="text-gray-400 truncate max-w-[150px]">${fileName}</span>
                        <a href="${dlUrl}" download="${fileName}" class="inline-flex items-center gap-1 font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Baixar
                        </a>
                    </div>
                </div>`;
            }

            // Mensagem de Vídeo
            if (message.type === 'video' && inlineHref) {
                const url = escapeHtml(inlineHref);
                const dlUrl = escapeHtml(downloadHref || url);
                return `<div class="space-y-1.5 max-w-[280px]">
                    <video controls preload="metadata" src="${url}" class="w-full max-h-[240px] rounded-xl border border-gray-200 shadow-sm bg-black"></video>
                    ${bodyText ? `<p class="text-xs whitespace-pre-wrap break-words">${bodyText}</p>` : ''}
                    <div class="flex items-center justify-between text-[11px] pt-1">
                        <span class="text-gray-400 truncate max-w-[160px]">${fileName}</span>
                        <a href="${dlUrl}" download="${fileName}" class="inline-flex items-center gap-1 font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Baixar vídeo
                        </a>
                    </div>
                </div>`;
            }

            // Mensagem de Áudio
            if (message.type === 'audio' && inlineHref) {
                const url = escapeHtml(inlineHref);
                const dlUrl = escapeHtml(downloadHref || url);
                return `<div class="space-y-1.5 max-w-[280px]">
                    <div class="p-2 bg-white/90 rounded-xl border border-gray-200 shadow-sm">
                        <audio controls preload="metadata" src="${url}" class="w-full" style="height: 36px;"></audio>
                        <div class="flex items-center justify-between text-[11px] pt-1.5 px-1">
                            <span class="text-gray-400 text-[10px]">Mensagem de voz</span>
                            <a href="${dlUrl}" download="${fileName}" class="font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                                Baixar áudio
                            </a>
                        </div>
                    </div>
                    ${bodyText ? `<p class="text-xs whitespace-pre-wrap break-words">${bodyText}</p>` : ''}
                </div>`;
            }

            // Mensagem de Documento / PDF / Arquivo Genérico
            if (message.type === 'document' || downloadHref) {
                const dlUrl = escapeHtml(downloadHref || inlineHref || '#');
                const isPdf = mime.includes('pdf') || fileName.toLowerCase().endsWith('.pdf');
                const isZip = mime.includes('zip') || mime.includes('rar') || fileName.toLowerCase().endsWith('.zip');
                const isDoc = mime.includes('word') || mime.includes('document') || fileName.toLowerCase().endsWith('.docx');
                const isSheet = mime.includes('sheet') || mime.includes('excel') || fileName.toLowerCase().endsWith('.xlsx');

                const icon = isPdf ? '📄 PDF' : (isZip ? '🗜️ ZIP' : (isDoc ? '📝 DOC' : (isSheet ? '📊 XLS' : '📁 ARQUIVO')));
                const iconBg = isPdf ? 'bg-red-100 text-red-700' : (isZip ? 'bg-amber-100 text-amber-700' : (isDoc ? 'bg-blue-100 text-blue-700' : (isSheet ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700')));

                return `<div class="p-3 bg-white/95 rounded-xl border border-gray-200 shadow-sm text-xs space-y-2 max-w-[290px]">
                    <div class="flex items-center gap-2.5">
                        <div class="px-2 py-1.5 rounded-lg ${iconBg} font-black text-[11px] flex-shrink-0 tracking-wider">
                            ${icon}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-gray-900 truncate" title="${fileName}">${fileName}</p>
                            <p class="text-[10px] text-gray-400 uppercase">${escapeHtml(message.mime_type || 'Anexo')}</p>
                        </div>
                    </div>
                    ${bodyText ? `<p class="text-xs text-gray-700 whitespace-pre-wrap break-words">${bodyText}</p>` : ''}
                    <div class="pt-2 border-t border-gray-100 flex items-center justify-between gap-2">
                        ${isPdf && inlineHref ? `<a href="${escapeHtml(inlineHref)}" target="_blank" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 underline">Visualizar</a>` : '<span></span>'}
                        <a href="${dlUrl}" download="${fileName}"
                           class="inline-flex items-center gap-1 text-[11px] font-bold text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1 rounded-lg transition-colors shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Baixar Arquivo
                        </a>
                    </div>
                </div>`;
            }

            return `<p class="whitespace-pre-wrap break-words">${bodyText}</p>`;
        },
    };
}

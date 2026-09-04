// resources/js/master.js

document.addEventListener('DOMContentLoaded', () => {
    /**
     * Aplicando globalThis para atender à regra Sonar S7764.
     * Substitui o uso de 'window' para uma abordagem universal e segura.
     */
    const config = globalThis.AppConfig;
    
    // Abortar se não houver usuário logado ou configuração definida no Blade
    if (!config || !config.userId) return;

    const badge = document.getElementById('notification-badge');
    const contentArea = document.querySelector('.notifications-content');

    const checkNotifications = () => {
        // globalThis.axios já foi configurado no bootstrap.js
        globalThis.axios.post(config.notificationRoute, { user: config.userId })
            .then(response => {
                const data = response.data;
                let unreadCount = 0;
                let html = '';

                if (Array.isArray(data)) {
                    data.forEach(item => {
                        if (item.status == 1) unreadCount++;

                        html += `
                            <div class="p-2 border-bottom ${item.status == 1 ? 'bg-light' : ''}">
                                <a href="${config.baseUrl}/${item.action}" class="text-decoration-none text-dark d-flex align-items-start gap-2">
                                    <img src="${config.assetImg}/${item.image}" width="30" alt="ícone">
                                    <div>
                                        <p class="mb-0 small fw-bold">${item.content}</p>
                                        <small class="text-muted">${item.time}</small>
                                    </div>
                                </a>
                            </div>`;
                    });
                }

                // Atualiza o Badge de Notificações
                if (badge) {
                    badge.innerText = unreadCount > 99 ? '99+' : unreadCount;
                    badge.style.display = unreadCount > 0 ? 'inline-block' : 'none';
                }

                // Atualiza o Conteúdo da Caixa de Notificações
                if (contentArea) {
                    contentArea.innerHTML = html || '<p class="p-3 text-center text-muted">Nenhuma notificação</p>';
                }
            })
            .catch(error => {
                console.error('Erro de Notificação:', error);
            })
            .finally(() => {
                /**
                 * Polling recursivo a cada 15 segundos.
                 * Mantém a verificação ativa enquanto a página estiver aberta.
                 */
                setTimeout(checkNotifications, 15000);
            });
    };

    // Inicia a primeira verificação imediatamente
    checkNotifications();
});

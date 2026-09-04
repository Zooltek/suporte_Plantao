// resources/js/company-form.js

document.addEventListener('DOMContentLoaded', () => {
    const config = globalThis.CompanyConfig;
    const stateSelect = document.getElementById('state');
    const citiesSelect = document.getElementById('cities');

    if (!stateSelect || !citiesSelect) return;

    stateSelect.addEventListener('change', async function() {
        const stateId = this.value;

        if (!stateId) return;

        try {
            if (!globalThis.axios) {
                throw new Error('axios não está disponível em globalThis');
            }

            const response = await globalThis.axios.post(config.citiesRoute, {
                state_id: stateId
            });

            citiesSelect.innerHTML = typeof response.data === 'string' ? response.data : String(response.data);
            
        } catch (error) {
            console.error('Erro ao carregar cidades:', error);
            citiesSelect.innerHTML = '<option value="">Erro ao carregar</option>';
        } finally {
            const event = new CustomEvent('cities-loaded');

            if (typeof globalThis.dispatchEvent === 'function') {
                globalThis.dispatchEvent(event);
            } else if (typeof window !== 'undefined' && typeof window.dispatchEvent === 'function') {
                window.dispatchEvent(event);
            }

            const alpineRoot = document.querySelector('[x-data]');
            if (alpineRoot && alpineRoot.__x && alpineRoot.__x.$data) {
                try {
                    alpineRoot.__x.$data.loadingCities = false;
                } catch (err) {
                    console.warn('Não foi possível atualizar loadingCities:', err);
                }
            }
        }
    });
});


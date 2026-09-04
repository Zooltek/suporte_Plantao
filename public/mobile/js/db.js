/**
 * PlantaoDB - Camada de Banco de Dados Local Offline (IndexedDB Nativo)
 * Zero dependências externas, 100% funcional sem internet.
 */
class PlantaoDB {
    constructor() {
        this.dbName = 'AmuraPlantaoMobileDB';
        this.dbVersion = 1;
        this.db = null;
    }

    /**
     * Inicializa e abre a conexão com o banco IndexedDB
     */
    async init() {
        if (this.db) return this.db;

        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Tabela de Configurações do App (chave/valor)
                if (!db.objectStoreNames.contains('settings')) {
                    db.createObjectStore('settings', { keyPath: 'key' });
                }

                // Tabela de Clientes (cache local para busca rápida)
                if (!db.objectStoreNames.contains('customers')) {
                    const store = db.createObjectStore('customers', { keyPath: 'id' });
                    store.createIndex('code', 'code', { unique: false });
                    store.createIndex('trade_name', 'trade_name', { unique: false });
                }

                // Tabela de Categorias e Subcategorias
                if (!db.objectStoreNames.contains('categories')) {
                    db.createObjectStore('categories', { keyPath: 'id' });
                }

                // Tabela de Status
                if (!db.objectStoreNames.contains('statuses')) {
                    db.createObjectStore('statuses', { keyPath: 'id' });
                }

                // Tabela de Agentes/Técnicos
                if (!db.objectStoreNames.contains('agents')) {
                    db.createObjectStore('agents', { keyPath: 'id' });
                }

                // Tabela de Turnos de Plantão (Sobreaviso)
                if (!db.objectStoreNames.contains('shifts')) {
                    const store = db.createObjectStore('shifts', { keyPath: 'uuid' });
                    store.createIndex('status', 'status', { unique: false });
                    store.createIndex('synced', 'synced', { unique: false });
                }

                // Tabela de Atendimentos Realizados
                if (!db.objectStoreNames.contains('attendances')) {
                    const store = db.createObjectStore('attendances', { keyPath: 'uuid' });
                    store.createIndex('shift_uuid', 'shift_uuid', { unique: false });
                    store.createIndex('synced', 'synced', { unique: false });
                }
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                resolve(this.db);
            };

            request.onerror = (event) => {
                console.error('[PlantaoDB] Erro ao abrir IndexedDB:', event.target.error);
                reject(event.target.error);
            };
        });
    }

    /**
     * Obter transação para leitura ou escrita
     */
    _getStore(storeName, mode = 'readonly') {
        const tx = this.db.transaction(storeName, mode);
        return tx.objectStore(storeName);
    }

    // ─── MÉTODOS DE CONFIGURAÇÃO (Settings) ───────────────────────────
    async getSetting(key, defaultValue = null) {
        await this.init();
        return new Promise((resolve) => {
            const store = this._getStore('settings', 'readonly');
            const req = store.get(key);
            req.onsuccess = () => resolve(req.result ? req.result.value : defaultValue);
            req.onerror = () => resolve(defaultValue);
        });
    }

    async setSetting(key, value) {
        await this.init();
        return new Promise((resolve, reject) => {
            const store = this._getStore('settings', 'readwrite');
            const req = store.put({ key, value });
            req.onsuccess = () => resolve(true);
            req.onerror = (e) => reject(e);
        });
    }

    // ─── DADOS MESTRES (Master Data) ──────────────────────────────────
    async saveMasterData({ customers = [], categories = [], statuses = [], agents = [] }) {
        await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction(['customers', 'categories', 'statuses', 'agents'], 'readwrite');

            // Salvar clientes
            if (customers.length > 0) {
                const custStore = tx.objectStore('customers');
                custStore.clear();
                customers.forEach(c => custStore.put(c));
            }

            // Salvar categorias
            if (categories.length > 0) {
                const catStore = tx.objectStore('categories');
                catStore.clear();
                categories.forEach(c => catStore.put(c));
            }

            // Salvar status
            if (statuses.length > 0) {
                const statStore = tx.objectStore('statuses');
                statStore.clear();
                statuses.forEach(s => statStore.put(s));
            }

            // Salvar agentes
            if (agents.length > 0) {
                const agStore = tx.objectStore('agents');
                agStore.clear();
                agents.forEach(a => agStore.put(a));
            }

            tx.oncomplete = () => resolve(true);
            tx.onerror = (e) => reject(e);
        });
    }

    async searchCustomers(term, limit = 20) {
        await this.init();
        const lower = term.toLowerCase().trim();
        return new Promise((resolve) => {
            const store = this._getStore('customers', 'readonly');
            const results = [];
            const req = store.openCursor();

            req.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    const c = cursor.value;
                    const match = (c.code && String(c.code).toLowerCase().includes(lower)) ||
                                  (c.trade_name && c.trade_name.toLowerCase().includes(lower)) ||
                                  (c.name && c.name.toLowerCase().includes(lower));

                    if (match) {
                        results.push(c);
                        if (results.length >= limit) return resolve(results);
                    }
                    cursor.continue();
                } else {
                    resolve(results);
                }
            };
            req.onerror = () => resolve([]);
        });
    }

    async getCustomerCount() {
        await this.init();
        return new Promise((resolve) => {
            const store = this._getStore('customers', 'readonly');
            const req = store.count();
            req.onsuccess = () => resolve(req.result || 0);
            req.onerror = () => resolve(0);
        });
    }

    async getCategories() {
        await this.init();
        return new Promise((resolve) => {
            const store = this._getStore('categories', 'readonly');
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => resolve([]);
        });
    }

    async getStatuses() {
        await this.init();
        return new Promise((resolve) => {
            const store = this._getStore('statuses', 'readonly');
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => resolve([]);
        });
    }

    async getAgents() {
        await this.init();
        return new Promise((resolve) => {
            const store = this._getStore('agents', 'readonly');
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => resolve([]);
        });
    }

    // ─── TURNOS DE PLANTÃO (Shifts) ───────────────────────────────────
    async saveShift(shift) {
        await this.init();
        return new Promise((resolve, reject) => {
            const store = this._getStore('shifts', 'readwrite');
            const req = store.put(shift);
            req.onsuccess = () => resolve(shift);
            req.onerror = (e) => reject(e);
        });
    }

    async getShift(uuid) {
        await this.init();
        return new Promise((resolve) => {
            const store = this._getStore('shifts', 'readonly');
            const req = store.get(uuid);
            req.onsuccess = () => resolve(req.result || null);
            req.onerror = () => resolve(null);
        });
    }

    async getAllShifts() {
        await this.init();
        return new Promise((resolve) => {
            const store = this._getStore('shifts', 'readonly');
            const req = store.getAll();
            req.onsuccess = () => {
                const list = req.result || [];
                list.sort((a, b) => new Date(b.started_at) - new Date(a.started_at));
                resolve(list);
            };
            req.onerror = () => resolve([]);
        });
    }

    // ─── ATENDIMENTOS (Attendances) ──────────────────────────────────
    async saveAttendance(attendance) {
        await this.init();
        return new Promise((resolve, reject) => {
            const store = this._getStore('attendances', 'readwrite');
            const req = store.put(attendance);
            req.onsuccess = () => resolve(attendance);
            req.onerror = (e) => reject(e);
        });
    }

    async deleteAttendance(uuid) {
        await this.init();
        return new Promise((resolve, reject) => {
            const store = this._getStore('attendances', 'readwrite');
            const req = store.delete(uuid);
            req.onsuccess = () => resolve(true);
            req.onerror = (e) => reject(e);
        });
    }

    async getAttendance(uuid) {
        await this.init();
        return new Promise((resolve) => {
            const store = this._getStore('attendances', 'readonly');
            const req = store.get(uuid);
            req.onsuccess = () => resolve(req.result || null);
            req.onerror = () => resolve(null);
        });
    }

    async getAllAttendances() {
        await this.init();
        return new Promise((resolve) => {
            const store = this._getStore('attendances', 'readonly');
            const req = store.getAll();
            req.onsuccess = () => {
                const list = req.result || [];
                list.sort((a, b) => new Date(b.started_at) - new Date(a.started_at));
                resolve(list);
            };
            req.onerror = () => resolve([]);
        });
    }

    async getAttendancesByShift(shiftUuid) {
        await this.init();
        return new Promise((resolve) => {
            const store = this._getStore('attendances', 'readonly');
            const index = store.index('shift_uuid');
            const req = index.getAll(shiftUuid);
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => resolve([]);
        });
    }

    async getPendingSyncItems() {
        await this.init();
        const allShifts = await this.getAllShifts();
        const allAttendances = await this.getAllAttendances();

        const pendingShifts = allShifts.filter(s => !s.synced);
        const pendingAttendances = allAttendances.filter(a => !a.synced);

        return {
            shifts: pendingShifts,
            attendances: pendingAttendances,
            totalPending: pendingShifts.length + pendingAttendances.length
        };
    }
}

// Instância global exportada
window.db = new PlantaoDB();

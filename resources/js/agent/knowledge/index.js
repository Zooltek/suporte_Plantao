/**
 * Alpine.js component para a listagem da EasyWiki (base de conhecimento).
 *
 * Arquivo separado da view Blade (SRP + convenção do projeto).
 * Registrado via: Alpine.data('knowledgeIndex', knowledgeIndex)
 */
export function knowledgeIndex() {
    return {
        searchQuery: '',
        selectedCategory: '',

        init() {
            // Lê query string ao inicializar
            const params = new URLSearchParams(window.location.search);
            this.searchQuery      = params.get('q') || '';
            this.selectedCategory = params.get('category_id') || '';
        },

        search() {
            const params = new URLSearchParams();
            if (this.searchQuery.trim())      params.set('q', this.searchQuery.trim());
            if (this.selectedCategory)        params.set('category_id', this.selectedCategory);
            window.location.search = params.toString();
        },
    };
}

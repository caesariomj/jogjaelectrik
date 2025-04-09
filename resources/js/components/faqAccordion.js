export default (items) => ({
    search: '',
    items: items,

    init() {
        let hash = window.location.hash.substring(1);

        if (hash) {
            this.search = decodeURIComponent(hash);
        }
    },

    get filteredItems() {
        return this.items.filter(
            (item) =>
                item.title.toLowerCase().includes(this.search.toLowerCase()) ||
                item.content.toLowerCase().includes(this.search.toLowerCase()),
        );
    },
});

export default (items) => ({
    search: '',
    items: items,

    get filteredItems() {
        return this.items.filter(
            (item) =>
                item.title.toLowerCase().includes(this.search.toLowerCase()) ||
                item.content.toLowerCase().includes(this.search.toLowerCase()),
        );
    },
});

export default (
    data = {
        products: [],
    },
) => ({
    products: data.products,
    currentIndex: 0,

    productsPerSlide: window.innerWidth >= 768 ? 4 : 2,
    touchStartX: null,
    touchEndX: null,
    swipeThreshold: 50,

    init() {
        window.addEventListener('resize', () => this.updateProductsPerSlide());

        this.updateProductsPerSlide();
    },

    updateProductsPerSlide() {
        this.productsPerSlide = window.innerWidth >= 768 ? 4 : 2;

        const maxIndex = Math.ceil(this.products.length / this.productsPerSlide) - 1;

        this.currentIndex = Math.min(this.currentIndex, maxIndex);
    },

    previous() {
        this.currentIndex = Math.max(this.currentIndex - 1, 0);
    },

    next() {
        const maxIndex = Math.ceil(this.products.length / this.productsPerSlide) - 1;

        this.currentIndex = Math.min(this.currentIndex + 1, maxIndex);
    },

    handleTouchStart(event) {
        this.touchStartX = event.touches[0].clientX;
    },

    handleTouchMove(event) {
        this.touchEndX = event.touches[0].clientX;
    },

    handleTouchEnd() {
        if (this.touchEndX) {
            if (this.touchStartX - this.touchEndX > this.swipeThreshold) {
                this.next();
            }
            if (this.touchStartX - this.touchEndX < -this.swipeThreshold) {
                this.previous();
            }
            this.touchStartX = null;
            this.touchEndX = null;
        }
    },

    transformX() {
        return `translateX(-${this.currentIndex * 100}%)`;
    },

    formatPrice(price) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
            minimumFractionDigits: 0,
        }).format(price);
    },
});

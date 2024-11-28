export default (
    data = {
        products: [],
    },
) => ({
    products: data.products,
    currentIndex: 0,
    productsPerSlide: 3,
    showControls: false,
    touchStartX: null,
    touchEndX: null,
    swipeThreshold: 50,
    breakpoints: {
        md: 2,
        lg: 3,
    },

    init() {
        this.updateProductsPerSlide();

        this.updateShowControls();

        window.matchMedia('(max-width: 768px)').addEventListener('change', () => {
            this.updateProductsPerSlide();
            this.updateShowControls();
        });
    },

    updateProductsPerSlide() {
        const screenWidth = window.innerWidth;

        if (screenWidth < 1024) {
            this.productsPerSlide = this.breakpoints.md;
        } else {
            this.productsPerSlide = this.breakpoints.lg;
        }
    },

    updateShowControls() {
        this.showControls = this.products.length > (window.matchMedia('(max-width: 1024px)').matches ? 2 : 3);
    },

    get chunkedProducts() {
        const paddedProducts = [...this.products];
        const remainder = this.products.length % this.productsPerSlide;

        if (remainder > 0) {
            const placeholdersNeeded = this.productsPerSlide - remainder;
            for (let i = 0; i < placeholdersNeeded; i++) {
                paddedProducts.push({ id: `placeholder-${i}`, isPlaceholder: true });
            }
        }

        return paddedProducts.reduce((resultArray, item, index) => {
            const chunkIndex = Math.floor(index / this.productsPerSlide);

            if (!resultArray[chunkIndex]) {
                resultArray[chunkIndex] = [];
            }

            resultArray[chunkIndex].push(item);

            return resultArray;
        }, []);
    },

    get totalSlides() {
        return this.chunkedProducts.length;
    },

    previous() {
        this.currentIndex = this.currentIndex > 0 ? this.currentIndex - 1 : this.totalSlides - 1;
    },

    next() {
        this.currentIndex = this.currentIndex < this.totalSlides - 1 ? this.currentIndex + 1 : 0;
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

    formatPrice(price) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
            minimumFractionDigits: 0,
        }).format(price);
    },
});

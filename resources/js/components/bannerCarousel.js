export default (
    data = {
        slides: [],
        intervalTime: 0,
    },
) => ({
    slides: data.slides,
    autoplayIntervalTime: data.intervalTime,
    currentSlideIndex: 1,
    touchStartX: null,
    touchEndX: null,
    swipeThreshold: 50,
    isPaused: false,
    autoplayInterval: null,

    init() {
        if (this.autoplayIntervalTime > 0) {
            this.autoplay();
        }
    },

    previous() {
        if (this.currentSlideIndex > 1) {
            this.currentSlideIndex = this.currentSlideIndex - 1;
        } else {
            this.currentSlideIndex = this.slides.length;
        }
    },

    next() {
        if (this.currentSlideIndex < this.slides.length) {
            this.currentSlideIndex = this.currentSlideIndex + 1;
        } else {
            this.currentSlideIndex = 1;
        }
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

    autoplay() {
        this.autoplayInterval = setInterval(() => {
            if (!this.isPaused) {
                this.next();
            }
        }, this.autoplayIntervalTime);
    },

    setAutoplayIntervalTime(newIntervalTime) {
        clearInterval(this.autoplayInterval);
        this.autoplayIntervalTime = newIntervalTime;
        this.autoplay();
    },
});

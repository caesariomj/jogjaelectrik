export default (initialImage = '') => ({
    selectedImage: initialImage,

    scrollPosition: 0,
    itemHeight: 0,
    sliderHeight: 0,
    totalHeight: 0,
    isAtTop: true,
    isAtBottom: false,

    init() {
        this.itemHeight = this.$refs.slider.querySelector('li').offsetHeight + 8;
        this.sliderHeight = this.$refs.slider.offsetHeight;
        this.totalHeight = this.itemHeight * this.$refs.slider.children.length;

        this.updateButtonVisibility();
    },

    scrollUp() {
        if (this.scrollPosition > 0) {
            this.scrollPosition -= this.itemHeight;
            this.updateButtonVisibility();
        }
    },

    scrollDown() {
        if (this.scrollPosition + this.sliderHeight < this.totalHeight) {
            this.scrollPosition += this.itemHeight;
            this.updateButtonVisibility();
        }
    },

    updateButtonVisibility() {
        this.isAtTop = this.scrollPosition <= 0;
        this.isAtBottom = this.scrollPosition + this.sliderHeight >= this.totalHeight;
    },

    selectImage(imageUrl) {
        this.selectedImage = imageUrl;
    },
});

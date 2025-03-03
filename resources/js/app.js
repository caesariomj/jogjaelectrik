import './bootstrap';

import bannerCarouselComponent from './components/bannerCarousel';
import comboboxComponent from './components/combobox';
import datepickerComponent from './components/datepicker';
import dropdownComponent from './components/dropdown';
import faqAccordionComponent from './components/faqAccordion';
import fileInputComponent from './components/fileInput';
import modalComponent from './components/modal';
import productImageGalleryComponent from './components/productImageGallery';
import productSliderComponent from './components/productSlider';

document.addEventListener('alpine:init', () => {
    Alpine.data('bannerCarousel', bannerCarouselComponent);
    Alpine.data('combobox', comboboxComponent);
    Alpine.data('dropdown', dropdownComponent);
    Alpine.data('faqAccordion', faqAccordionComponent);
    Alpine.data('fileInput', fileInputComponent);
    Alpine.data('flatpickr', datepickerComponent);
    Alpine.data('modal', modalComponent);
    Alpine.data('productImageGallery', productImageGalleryComponent);
    Alpine.data('productSlider', productSliderComponent);
});

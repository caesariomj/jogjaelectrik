import './bootstrap';

import bannerCarouselComponent from './components/bannerCarousel';
import comboboxComponent from './components/combobox';
import datepickerComponent from './components/datepicker';
import dropdownComponent from './components/dropdown';
import faqAccordionComponent from './components/faqAccordion';
import fileInputComponent from './components/fileInput';
import productSliderComponent from './components/productSlider';

document.addEventListener('alpine:init', () => {
    Alpine.data('bannerCarousel', bannerCarouselComponent);
    Alpine.data('combobox', comboboxComponent);
    Alpine.data('dropdown', dropdownComponent);
    Alpine.data('faqAccordion', faqAccordionComponent);
    Alpine.data('fileInput', fileInputComponent);
    Alpine.data('flatpickr', datepickerComponent);
    Alpine.data('productSlider', productSliderComponent);
});

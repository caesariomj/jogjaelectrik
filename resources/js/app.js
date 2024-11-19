import './bootstrap';

import datepickerComponent from './components/datepicker';
import dropdownComponent from './components/dropdown';

document.addEventListener('alpine:init', () => {
    Alpine.data('dropdown', dropdownComponent);

    Alpine.data(
        'combobox',
        (
            comboboxData = {
                allOptions: [],
                selectedOption: null,
                instanceName: '',
            },
        ) => ({
            options: comboboxData.allOptions,
            selectedOption: comboboxData.selectedOption,
            instanceName: comboboxData.instanceName,
            isOpen: false,
            openedWithKeyboard: false,

            init() {
                if (this.selectedOption) {
                    const entireData = this.findDataByValue(this.options, this.selectedOption);
                    if (entireData) {
                        this.setSelectedOption(entireData);
                    }
                }
            },

            findDataByValue(data, value) {
                return data.find((item) => item.value === value);
            },

            setSelectedOption(option) {
                this.selectedOption = option;
                this.isOpen = false;
                this.openedWithKeyboard = false;
                this.$refs.hiddenTextField.value = option.value;
                this.$wire.handleComboboxChange(option.value, this.instanceName);
            },

            getFilteredOptions(query) {
                this.options = comboboxData.allOptions.filter((option) =>
                    option.label.toLowerCase().includes(query.toLowerCase()),
                );
                if (this.options.length === 0) {
                    this.$refs.noResultsMessage.classList.remove('hidden');
                } else {
                    this.$refs.noResultsMessage.classList.add('hidden');
                }
            },

            handleKeydownOnOptions(event) {
                if (
                    (event.keyCode >= 65 && event.keyCode <= 90) ||
                    (event.keyCode >= 48 && event.keyCode <= 57) ||
                    event.keyCode === 8
                ) {
                    this.$refs.searchField.focus();
                }
            },
        }),
    );

    Alpine.data('flatpickr', datepickerComponent);
});

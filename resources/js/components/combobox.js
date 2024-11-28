export default (
    data = {
        allOptions: [],
        selectedOption: null,
        instanceName: '',
    },
) => ({
    options: data.allOptions,
    selectedOption: data.selectedOption,
    instanceName: data.instanceName,
    isOpen: false,
    openedWithKeyboard: false,

    init() {
        if (this.autoplayIntervalTime > 0) {
            this.autoplay();
        }
    },

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
        this.options = data.allOptions.filter((option) => option.label.toLowerCase().includes(query.toLowerCase()));
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
});

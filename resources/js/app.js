import './bootstrap';
import { createPopper } from '@popperjs/core/lib/popper-lite';

window.Popper = { createPopper };

document.addEventListener('alpine:init', () => {
    Alpine.data('dropdownPopper', () => ({
        open: false,
        popperInstance: null,

        init() {
            this.createPopper();

            this.$watch('open', (value) => {
                if (value) {
                    this.popperInstance?.update();
                }
            });
        },

        createPopper() {
            if (this.popperInstance) {
                this.popperInstance.destroy();
            }

            const placement = this.$el.dataset.placement || 'bottom-start';

            this.popperInstance = Popper.createPopper(this.$refs.button, this.$refs.panel, {
                placement: placement,
                strategy: 'fixed',
                modifiers: [
                    {
                        name: 'offset',
                        options: {
                            offset: [0, 8],
                        },
                    },
                    {
                        name: 'preventOverflow',
                        options: {
                            boundary: 'viewport',
                            padding: 8,
                        },
                    },
                    {
                        name: 'flip',
                        options: {
                            fallbackPlacements: ['bottom-start', 'bottom-end', 'top-start', 'top-end'],
                        },
                    },
                    {
                        name: 'computeStyles',
                        options: {
                            gpuAcceleration: false,
                            adaptive: false,
                        },
                    },
                    {
                        name: 'eventListeners',
                        enabled: true,
                        options: {
                            scroll: true,
                            resize: true,
                        },
                    },
                ],
            });
        },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => {
                    this.popperInstance.update();
                });
            }
        },

        close() {
            this.open = false;
        },
    }));

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
});

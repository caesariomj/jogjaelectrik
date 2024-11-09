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
});

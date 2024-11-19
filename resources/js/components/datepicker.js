import flatpickr from 'flatpickr';
import id from 'flatpickr/dist/l10n/id';

export default (model, options = {}) => ({
    value: model,

    init() {
        flatpickr(this.$refs.input, {
            dateFormat: 'd-m-Y',
            locale: 'id',
            defaultDate: this.value,
            minDate: options.minDate || 'today',

            onChange: (selectedDates, dateStr) => {
                this.value = dateStr;
            },
        });

        this.$watch('value', (value) => {
            this.$refs.input._flatpickr.setDate(value);
        });
    },
});

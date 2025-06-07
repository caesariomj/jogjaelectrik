import { Chart } from 'chart.js/auto';

export default (
    params = {
        labels: [],
        data: [],
        datasetLabel: '',
    },
) => ({
    ...params,

    init() {
        const ctx = this.$refs.canvas.getContext('2d');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.labels,
                datasets: [
                    {
                        label: this.datasetLabel,
                        data: this.data,
                        fill: true,
                        backgroundColor: 'rgba(253, 87, 34, 0.25)',
                        borderColor: 'rgba(253, 87, 34, 1)',
                        tension: 0,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            boxWidth: 15,
                            padding: 15,
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return Number.isInteger(value) ? value : null;
                            },
                            stepSize: 1,
                        },
                    },
                },
            },
        });
    },
});

import { Controller } from '@hotwired/stimulus';
import Chart from 'chart.js/auto';

export default class extends Controller {
    static values = {
        categories: Array,
        counts: Array,
        total: Number
    }

    connect() {
        this.initChart();
    }

    initChart() {
        const ctx = this.element.getContext('2d');

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: this.categoriesValue,
                datasets: [{
                    data: this.countsValue,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = this.totalValue;
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
}
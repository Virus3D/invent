import { Controller } from '@hotwired/stimulus';
import { Popover } from 'bootstrap';

export default class extends Controller {
    static targets = ['trigger'];
    static values = {
        specs: Object,
        labels: Object,
        title: String,
        placement: { type: String, default: 'right' },
        trigger: { type: String, default: 'click hover' },
        emptyMessage: { type: String, default: 'Характеристики не указаны' }
    };

    connect() {
        // Создаём HTML-контент на основе переданного объекта specs
        const content = this.buildContent();

        // Инициализируем Bootstrap Popover
        this.popover = new Popover(this.triggerTarget, {
            title: this.titleValue,
            content: content,
            html: true,
            placement: this.placementValue,
            trigger: this.triggerValue,
            container: 'body'
        });
    }

    disconnect() {
        // Уничтожаем popover при удалении элемента из DOM
        if (this.popover) {
            this.popover.dispose();
        }
    }

    buildContent() {
        const specs = this.specsValue || {};
        const labels = this.labelsValue || {};
        const keys = Object.keys(specs).filter(key => specs[key] !== null && specs[key] !== '');

        if (keys.length === 0) {
            return `<p class="text-muted mb-0">${this.emptyMessageValue}</p>`;
        }

        let html = '<dl class="row small mb-0">';
        keys.forEach(key => {
            const value = specs[key];
            const label = labels[key];
            html += `<dt class="col-sm-5">${label}:</dt>`;
            html += `<dd class="col-sm-7">${value}</dd>`;
        });
        html += '</dl>';

        return html;
    }
}
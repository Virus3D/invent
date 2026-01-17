import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['checkboxes', 'selectAll', 'counter'];
    static values = {
        selectAllText: String,
        deselectAllText: String,
        noItemsText: String
    }

    connect() {
        this.updateCounter();
    }

    toggleAll(event) {
        event.preventDefault();

        const allChecked = this.checkboxesTargets.every(checkbox => checkbox.checked);

        this.checkboxesTargets.forEach(checkbox => {
            checkbox.checked = !allChecked;
        });

        this.selectAllTarget.innerHTML = allChecked
            ? `<i class="bi bi-check-square"></i> ${this.selectAllTextValue}`
            : `<i class="bi bi-square"></i> ${this.deselectAllTextValue}`;

        this.updateCounter();
    }

    updateCounter() {
        const selectedCount = this.checkboxesTargets.filter(checkbox => checkbox.checked).length;

        if (selectedCount === 0) {
            this.counterTarget.innerHTML = `
                <span class="text-muted">
                    <i class="bi bi-info-circle"></i> ${this.noItemsTextValue}
                </span>
            `;
        } else {
            this.counterTarget.innerHTML = `
                <span class="badge bg-primary">
                    <i class="bi bi-check-circle"></i>
                    ${selectedCount} ${this.getPluralForm(selectedCount)}
                </span>
            `;
        }
    }

    getPluralForm(count) {
        const lastDigit = count % 10;
        const lastTwoDigits = count % 100;

        if (lastTwoDigits >= 11 && lastTwoDigits <= 19) {
            return 'выбрано';
        }

        if (lastDigit === 1) {
            return 'выбран';
        }

        if (lastDigit >= 2 && lastDigit <= 4) {
            return 'выбрано';
        }

        return 'выбрано';
    }

    validateForm(event) {
        const selectedCount = this.checkboxesTargets.filter(checkbox => checkbox.checked).length;

        if (selectedCount === 0) {
            event.preventDefault();
            this.showToast('Пожалуйста, выберите хотя бы один объект для перемещения', 'warning');
        }
    }

    showToast(message, type = 'info') {
        // Создаем toast уведомление
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        const container = document.querySelector('.toast-container') || this.createToastContainer();
        container.appendChild(toast);

        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    }
}
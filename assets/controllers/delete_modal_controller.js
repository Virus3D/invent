import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    static targets = ['modal', 'locationName', 'form', 'token'];

    connect() {
        // Инициализируем модальное окно Bootstrap
        this.modal = new Modal(this.modalTarget);
    }

    open(event) {
        if (event) {
            event.preventDefault();
        }

        const button = event.currentTarget;
        const locationId = button.dataset.locationId;
        const locationName = button.dataset.locationName;
        const deleteUrl = button.dataset.deleteUrl;
        const csrfToken = button.dataset.csrfToken;

        // Устанавливаем данные в модальное окно
        this.locationNameTarget.textContent = locationName;
        this.formTarget.action = deleteUrl;
        this.tokenTarget.value = csrfToken;
        this.tokenTarget.name = '_token'; // Добавляем имя для CSRF токена

        // Показываем модальное окно
        this.modal.show();
    }

    // Опционально: очистка при закрытии
    clear() {
        this.locationNameTarget.textContent = '';
        this.formTarget.action = '';
        this.tokenTarget.value = '';
    }
}
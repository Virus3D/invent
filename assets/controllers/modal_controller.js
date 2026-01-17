import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    static targets = ['modal', 'title', 'body', 'confirmButton', 'cancelButton']

    static values = {
        show: { type: Boolean, default: false },
        type: { type: String, default: 'confirm' } // confirm, alert, prompt, custom
    }

    connect() {
        // Инициализируем Bootstrap модалку
        this.modal = new Modal(this.modalTarget);

        // Если нужно показать сразу
        if (this.showValue) {
            setTimeout(() => this.open(), 100);
        }
    }

    // Открыть модальное окно
    open(event) {
        if (event) {
            event.preventDefault();
        }
        this.modal.show();
    }

    // Закрыть модальное окно
    close(event) {
        if (event) {
            event.preventDefault();
        }
        this.modal.hide();
    }

    // Подтвердить действие
    confirm(event) {
        if (event) {
            event.preventDefault();
        }

        // Эмитируем событие подтверждения
        this.dispatch('confirmed');
        this.close();
    }

    // Отменить действие
    cancel(event) {
        if (event) {
            event.preventDefault();
        }

        // Эмитируем событие отмены
        this.dispatch('cancelled');
        this.close();
    }
}
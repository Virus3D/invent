import { Controller } from '@hotwired/stimulus';
import { trans } from '../translator';

export default class extends Controller {
    static values = {
        currentLocationId: { type: String, default: '' },
        showUrl: { type: String, default: '' },
        submitUrl: { type: String, default: '' }
    }

    static targets = [
        'form',
        'fromLocation',
        'toLocation',
        'movedBy',
        'submitButton',
        'errorContainer'
    ]

    connect() {
        // Устанавливаем текущее местоположение
        this.setCurrentLocation();

        // Восстанавливаем сохраненное имя из localStorage
        this.restoreSavedMovedBy();

        // Инициализируем валидацию
        this.initializeValidation();
    }

    // Установка текущего местоположения
    setCurrentLocation() {
        if (this.currentLocationIdValue && this.hasFromLocationTarget) {
            this.fromLocationTarget.value = this.currentLocationIdValue;
        }
    }

    // Восстановление сохраненного имени
    restoreSavedMovedBy() {
        if (this.hasMovedByTarget) {
            const lastMovedBy = localStorage.getItem('lastMovedBy');
            if (lastMovedBy && !this.movedByTarget.value) {
                this.movedByTarget.value = lastMovedBy;
            }
        }
    }

    // Сохранение имени в localStorage
    saveMovedBy() {
        if (this.hasMovedByTarget && this.movedByTarget.value) {
            localStorage.setItem('lastMovedBy', this.movedByTarget.value);
        }
    }

    // Инициализация валидации
    initializeValidation() {
        if (this.hasToLocationTarget) {
            this.validateToLocation();
            this.toLocationTarget.addEventListener('change', () => this.validateToLocation());
        }

        if (this.hasMovedByTarget) {
            this.validateMovedBy();
            this.movedByTarget.addEventListener('input', () => this.validateMovedBy());
        }
    }

    // Валидация целевой локации
    validateToLocation() {
        if (!this.toLocationTarget.value) {
            this.showFieldError(this.toLocationTarget, trans('move.form.to_location_required'));
            return false;
        } else {
            this.hideFieldError(this.toLocationTarget);
            return true;
        }
    }

    // Валидация имени перемещающего
    validateMovedBy() {
        if (!this.movedByTarget.value.trim()) {
            this.showFieldError(this.movedByTarget, trans('move.form.moved_by_required'));
            return false;
        } else {
            this.hideFieldError(this.movedByTarget);
            return true;
        }
    }

    // Показать ошибку поля
    showFieldError(field, message) {
        field.classList.add('is-invalid');

        let errorElement = field.parentElement.querySelector('.invalid-feedback');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'invalid-feedback';
            field.parentElement.appendChild(errorElement);
        }

        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }

    // Скрыть ошибку поля
    hideFieldError(field) {
        field.classList.remove('is-invalid');

        const errorElement = field.parentElement.querySelector('.invalid-feedback');
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }

    // Валидация всей формы
    validateForm() {
        const isToLocationValid = this.validateToLocation();
        const isMovedByValid = this.validateMovedBy();

        return isToLocationValid && isMovedByValid;
    }

    // Обработка отправки формы
    async submitForm(event) {
        event.preventDefault();
        event.stopPropagation();

        // Скрываем предыдущие общие ошибки
        this.hideGeneralError();

        // Валидация
        if (!this.validateForm()) {
            this.showGeneralError(trans('form.validation.failed'));
            return false;
        }

        // Сохраняем имя в localStorage
        this.saveMovedBy();

        // Показываем индикатор загрузки
        this.showLoading();

        try {
            // Подготавливаем данные формы
            const formData = new FormData(this.formTarget);

            // Отправляем AJAX запрос
            const response = await fetch(this.submitUrlValue || this.formTarget.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                await this.handleSuccess(data);
            } else {
                this.handleError(data);
            }
        } catch (error) {
            console.error('Error:', error);
            this.handleNetworkError();
        } finally {
            this.hideLoading();
        }

        return false;
    }

    // Показать индикатор загрузки
    showLoading() {
        if (this.hasSubmitButtonTarget) {
            const originalText = this.submitButtonTarget.innerHTML;
            this.submitButtonTarget.setAttribute('data-original-text', originalText);
            this.submitButtonTarget.innerHTML = `
                <span class="spinner-border spinner-border-sm"></span> ${trans('common.messages.saving')}
            `;
            this.submitButtonTarget.disabled = true;
        }
    }

    // Скрыть индикатор загрузки
    hideLoading() {
        if (this.hasSubmitButtonTarget) {
            const originalText = this.submitButtonTarget.getAttribute('data-original-text');
            if (originalText) {
                this.submitButtonTarget.innerHTML = originalText;
            } else {
                this.submitButtonTarget.innerHTML = `
                    <i class="bi bi-check-circle"></i> ${trans('move.form.submit')}
                `;
            }
            this.submitButtonTarget.disabled = false;
        }
    }

    // Обработка успешного сохранения
    async handleSuccess(data) {
        // Показываем уведомление об успехе
        this.showSuccessNotification(data.message || trans('move.form.success'));

        // Перенаправляем на страницу объекта
        if (data.redirectUrl) {
            setTimeout(() => {
                window.location.href = data.redirectUrl;
            }, 1500);
        } else if (this.showUrlValue) {
            setTimeout(() => {
                window.location.href = this.showUrlValue;
            }, 1500);
        }
    }

    // Показать уведомление об успехе
    showSuccessNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'position-fixed top-0 end-0 p-3';
        notification.style.zIndex = '9999';

        notification.innerHTML = `
            <div class="toast show bg-success text-white" role="alert">
                <div class="toast-header bg-success text-white border-0">
                    <i class="bi bi-check-circle me-2"></i>
                    <strong class="me-auto">${trans('move.form.success')}</strong>
                    <button type="button" class="btn-close btn-close-white"
                            data-action="click->inventory-move#closeNotification"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;

        notification.setAttribute('data-inventory-move-target', 'notification');
        document.body.appendChild(notification);

        // Автоматически скрываем через 3 секунды
        setTimeout(() => {
            this.closeNotification();
        }, 3000);
    }

    // Закрыть уведомление
    closeNotification() {
        const notification = this.element.querySelector('[data-inventory-move-target="notification"]');
        if (notification) {
            notification.remove();
        }
    }

    // Обработка ошибок
    handleError(data) {
        if (data.errors) {
            this.displayValidationErrors(data.errors);
        } else {
            this.showGeneralError(data.message || trans('common.messages.error'));
        }
    }

    // Обработка сетевой ошибки
    handleNetworkError() {
        this.showGeneralError(trans('common.messages.network_error'));
    }

    // Отображение ошибок валидации
    displayValidationErrors(errors) {
        let errorHtml = '<ul class="mb-0">';

        if (Array.isArray(errors)) {
            errors.forEach(error => {
                errorHtml += `<li><strong>${error.field}:</strong> ${error.message}</li>`;
            });
        } else if (typeof errors === 'object') {
            Object.entries(errors).forEach(([field, message]) => {
                errorHtml += `<li><strong>${field}:</strong> ${message}</li>`;

                // Подсвечиваем проблемные поля
                const input = this.formTarget.querySelector(`[name*="${field}"]`);
                if (input) {
                    input.classList.add('is-invalid');
                    this.showFieldError(input, message);
                }
            });
        } else {
            errorHtml += `<li>${errors}</li>`;
        }

        errorHtml += '</ul>';

        this.showGeneralError(trans('form.validation.errors'), errorHtml);
    }

    // Показать общую ошибку
    showGeneralError(title, message = '') {
        let errorContainer = this.errorContainerTarget;

        errorContainer.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <div>
                    <strong>${title}</strong>
                    ${message}
                </div>
                <button type="button" class="btn-close ms-auto"
                        data-action="click->inventory-move#hideGeneralError"></button>
            </div>
        `;
        errorContainer.classList.remove('d-none');
    }

    // Скрыть общую ошибку
    hideGeneralError() {
        if (this.hasErrorContainerTarget) {
            this.errorContainerTarget.classList.add('d-none');
        }
    }

    // Сброс формы
    resetForm() {
        if (this.hasFormTarget) {
            this.formTarget.reset();
            this.setCurrentLocation();
            this.restoreSavedMovedBy();
            this.hideGeneralError();

            // Сбрасываем валидацию
            this.formTarget.classList.remove('was-validated');
            this.formTarget.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
        }
    }
}
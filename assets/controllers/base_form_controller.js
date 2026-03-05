import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';
import { trans } from '../translator';

/**
 * Базовый контроллер для форм с AJAX-отправкой
 * Предоставляет: валидацию, обработку ошибок, загрузку, модальные окна
 */
export default class extends Controller {
    static values = {
        submitUrl: String,
        showUrl: String,
        successMessage: String
    }

    static targets = [
        'form',
        'submitButton',
        'errorContainer'
    ]

    /**
     * Показать ошибку для конкретного поля
     */
    showFieldError(field, message) {
        field.classList.add('is-invalid');

        let errorElement = field.parentElement?.querySelector('.invalid-feedback');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'invalid-feedback';
            field.parentElement?.appendChild(errorElement);
        }

        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }

    /**
     * Скрыть ошибку поля
     */
    hideFieldError(field) {
        field.classList.remove('is-invalid');
        const errorElement = field.parentElement?.querySelector('.invalid-feedback');
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }

    /**
     * Очистить все ошибки валидации в форме
     */
    clearValidationErrors() {
        this.element?.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
            this.hideFieldError(el);
        });
    }

    /**
     * Отобразить ошибки валидации из ответа сервера
     */
    displayValidationErrors(errors, formRoot = null) {
        const root = formRoot || this.element;
        let errorHtml = '<ul class="mb-0">';

        const processError = (field, message) => {
            errorHtml += `<li><strong>${field}:</strong> ${message}</li>`;
            // Подсветка поля, если найдено
            const escapedField = field.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, '\\$&');
            const input = root.querySelector(`[name="${escapedField}"]`);
            if (input) {
                input.classList.add('is-invalid');
                this.showFieldError(input, message);
            }
        };

        if (Array.isArray(errors)) {
            errors.forEach(({ field, message }) => processError(field, message));
        } else if (typeof errors === 'object') {
            Object.entries(errors).forEach(([field, message]) => processError(field, message));
        } else {
            errorHtml += `<li>${errors}</li>`;
        }

        errorHtml += '</ul>';
        this.showError(trans('form.validation.failed'), errorHtml);
    }

    /**
     * Показать общую ошибку формы
     */
    showError(title, message = '') {
        const container = this.errorContainerTarget;
        if (!container) return;

        container.innerHTML = `
            <div class="d-flex align-items-start">
                <i class="bi bi-exclamation-triangle me-2 mt-1"></i>
                <div>
                    <strong>${title}</strong>
                    ${message}
                </div>
                <button type="button" class="btn-close ms-auto"
                        data-action="click->${this.identifier}#hideError"
                        aria-label="${trans('common.actions.close')}"></button>
            </div>
        `;
        container.classList.remove('d-none');
        container.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /**
     * Скрыть общую ошибку
     */
    hideError() {
        const container = this.errorContainerTarget;
        if (container) {
            container.classList.add('d-none');
            container.innerHTML = '';
        }
        this.clearValidationErrors();
    }

    /**
     * Показать индикатор загрузки на кнопке
     */
    showLoading(buttonTextKey = 'common.messages.saving') {
        if (!this.hasSubmitButtonTarget) return;

        const btn = this.submitButtonTarget;
        if (!btn.hasAttribute('data-original-text')) {
            btn.setAttribute('data-original-text', btn.innerHTML);
        }
        btn.innerHTML = `
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            ${trans(buttonTextKey)}
        `;
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
    }

    /**
     * Скрыть индикатор загрузки
     */
    hideLoading(defaultIcon = 'bi-check-circle', defaultTextKey = 'common.actions.save') {
        if (!this.hasSubmitButtonTarget) return;

        const btn = this.submitButtonTarget;
        const originalText = btn.getAttribute('data-original-text');

        btn.innerHTML = originalText || `
            <i class="bi ${defaultIcon}"></i> ${trans(defaultTextKey)}
        `;
        btn.disabled = false;
        btn.removeAttribute('aria-busy');
    }


    // Обработка успешного сохранения
    handleSuccess(data) {
        return this.handleSuccessResponse(data);
    }

    /**
     * Обработка успешного ответа: показ модалки + редирект
     */
    async handleSuccessResponse(data, modalId = 'successModal', messageKey = 'message') {
        const modalEl = document.getElementById(modalId);
        if (!modalEl) return;

        const modal = new Modal(modalEl);
        const msgEl = document.getElementById(`${modalId}Msg`);
        if (msgEl && data[messageKey]) {
            msgEl.innerHTML = data[messageKey];
        }

        // Поддержка Promise для редиректа после закрытия
        const redirectUrl = data.redirectUrl || this.showUrlValue;

        if (redirectUrl) {
            return new Promise((resolve) => {
                modalEl.addEventListener('hidden.bs.modal', () => {
                    window.location.href = redirectUrl;
                    resolve();
                }, { once: true });
                modal.show();
            });
        }

        modal.show();
        return Promise.resolve();
    }

    /**
     * Обработка ошибок ответа
     */
    handleError(data) {
        if (data.errors) {
            this.displayValidationErrors(data.errors);
        } else {
            this.showError(
                trans('common.modals.saving_error'),
                data.message || trans('common.messages.error')
            );
        }
    }

    /**
     * Обработка сетевой ошибки
     */
    handleNetworkError() {
        this.showError(
            trans('common.modals.network_error'),
            trans('common.messages.check_connection')
        );
    }

    /**
     * Стандартная отправка формы через AJAX
     * Переопределите beforeSubmit/afterSubmit для кастомизации
     */
    async submitForm(event, options = {}) {
        event?.preventDefault();
        event?.stopPropagation();

        this.hideError();

        // Хук перед валидацией
        if (typeof this.beforeValidate === 'function' && !(await this.beforeValidate())) {
            return false;
        }

        if (!this.validateForm?.()) {
            this.showError(trans('form.validation.fill_required_fields'));
            return false;
        }

        // Хук после валидации
        if (typeof this.afterValidate === 'function' && !(await this.afterValidate())) {
            return false;
        }

        const formData = options.formData || new FormData(this.formTarget);
        const url = options.url || this.submitUrlValue || this.formTarget?.action;
        const method = options.method || 'POST';
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            ...options.headers
        };

        this.showLoading(options.loadingTextKey);

        try {
            const response = await fetch(url, { method, body: formData, headers });

            // Определяем тип содержимого ответа
            const contentType = response.headers.get('content-type') || '';
            let data;

            if (contentType.includes('application/json')) {
                // Если пришёл JSON – парсим его
                data = await response.json();
            } else {
                // Если это не JSON (например, HTML-страница ошибки 500),
                // читаем как текст и создаём фейковый объект для handleError
                const text = await response.text();
                data = {
                    success: false,
                    message: text,
                    status: response.status,
                    statusText: response.statusText
                };
            }

            // Дальше работаем единообразно: проверяем флаг success
            if (data.success) {
                await this.handleSuccess(data);
                return true;
            } else {
                this.handleError(data);
                return false;
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.handleNetworkError();
            return false;
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Загрузка контента по URL с обработкой ошибок
     */
    async fetchWithFeedback(url, options = {}) {
        const {
            target,
            loadingTemplate = this.loadingTemplate?.(),
            errorKey = 'common.messages.load_error',
            onSuccess,
            onError
        } = options;

        if (target && loadingTemplate) {
            target.innerHTML = loadingTemplate;
        }

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                ...options.fetchOptions
            });
            if (!response.ok) {
                const text = await response.text();
                throw new Error(`HTTP ${response.status}: ${text}`);
            }

            const data = await response.json();

            if (data.success) {
                if (target && data.template) {
                    target.innerHTML = data.template;
                }
                await onSuccess?.(data);
                return data;
            } else {
                const msg = data.message || trans(errorKey);
                if (target) target.innerHTML = '';
                this.showError(trans('common.modals.load_error'), msg);
                await onError?.(data);
                return null;
            }
        } catch (error) {
            console.error('Fetch error:', error);
            if (target) target.innerHTML = '';
            this.showError(trans('common.modals.network_error'));
            await onError?.(error);
            return null;
        }
    }

    /**
     * Шаблон загрузки по умолчанию
     */
    loadingTemplate() {
        return `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">${trans('common.messages.loading')}</span>
                </div>
                <p class="mt-2 text-muted">${trans('common.messages.loading')}</p>
            </div>
        `;
    }

    /**
     * Валидация по умолчанию (переопределяется в дочерних контроллерах)
     */
    validateForm() {
        return true;
    }
}
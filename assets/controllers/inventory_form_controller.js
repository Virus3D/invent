import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';
import { trans } from '../translator';

export default class extends Controller {
    static values = {
        categorySpecsUrl: String,
        updateUrl: String,
        createUrl: String,
        itemId: { type: Number, default: 0 },
        currentCategory: String,
        currentSpecifications: { type: String, default: '{}' }
    }

    static targets = [
        'categorySelector',
        'specificationsSection',
        'submitButton',
        'formErrors',
        'form'
    ]

    connect() {
        let specs = this.currentSpecificationsValue;

        if (typeof specs === 'string') {
            try {
                specs = JSON.parse(specs);
            } catch {
                specs = {};
            }
        }

        if (Array.isArray(specs)) {
            specs = {};
        }

        this.specifications = specs || {};

        // Если при загрузке уже есть категория, убедимся что спецификации загружены
        if (this.currentCategoryValue && this.currentCategoryValue !== '') {
            this.loadCategorySpecifications(this.currentCategoryValue);
        }
    }

    // Обработчик изменения категории
    async onCategoryChange(event) {
        const newCategory = event.target.value;

        if (newCategory === this.currentCategoryValue) {
            return;
        }

        // Показываем индикатор загрузки
        this.specificationsSectionTarget.innerHTML = this.loadingTemplate();

        // Загружаем спецификации для новой категории
        await this.loadCategorySpecifications(newCategory);
    }

    // Загрузка спецификаций категории
    async loadCategorySpecifications(category) {
        try {
            const url = this.categorySpecsUrlValue.replace('__CATEGORY__', category);
            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                this.specificationsSectionTarget.innerHTML = data.template;
                this.currentCategoryValue = category;

                // Восстанавливаем сохраненные значения спецификаций
                if (this.specifications && Object.keys(this.specifications).length > 0) {
                    Object.entries(this.specifications).forEach(([key, value]) => {
                        const input = document.getElementById(`spec_${key}`);
                        if (input) {
                            input.value = value;
                        }
                    });
                }

                // Инициализируем валидацию для новых полей
                this.initializeSpecValidation();
            } else {
                this.showError(data.message || trans('inventory_item.specifications.load_error'));
                this.specificationsSectionTarget.innerHTML = '';
            }
        } catch (error) {
            console.error('Error loading category specs:', error);
            this.showError(trans('inventory_item.specifications.load_error'));
            this.specificationsSectionTarget.innerHTML = '';
        }
    }

    // Шаблон загрузки
    loadingTemplate() {
        return `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">` + trans('common.messages.loading') + `</span>
                </div>
                <p class="mt-2 text-muted">` + trans('inventory_item.specifications.load_error') + `</p>
            </div>
        `;
    }

    // Инициализация валидации спецификаций
    initializeSpecValidation() {
        const specInputs = this.specificationsSectionTarget.querySelectorAll('.spec-input');
        specInputs.forEach(input => {
            // Добавляем обработчик события blur
            input.addEventListener('blur', (e) => this.validateSpecField(e));

            // Сразу проверяем поле при инициализации
            this.validateField(input);
        });
    }

    // Валидация поля спецификации (через событие)
    validateSpecField(event) {
        const input = event.target;
        this.validateField(input);
    }

    // Валидация конкретного поля
    validateField(input) {
        const isRequired = this.isFieldRequired(input);

        if (isRequired && !input.value.trim()) {
            input.classList.add('is-invalid');
            this.showFieldError(input, trans('inventory_item.validation.required'));
            return false;
        } else {
            input.classList.remove('is-invalid');
            this.hideFieldError(input);
            return true;
        }
    }

    // Проверка, является ли поле обязательным
    isFieldRequired(input) {
        // Проверяем наличие required атрибута
        if (input.hasAttribute('required')) {
            return true;
        }

        // Проверяем наличие метки с классом required-field в той же группе
        const label = input.closest('.col-md-6, .col-12')?.querySelector('.required-field');
        return label !== null;
    }

    // Показать ошибку для конкретного поля
    showFieldError(input, message) {
        // Ищем или создаем элемент для отображения ошибки
        let errorElement = input.parentElement.querySelector('.invalid-feedback');

        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'invalid-feedback';
            input.parentElement.appendChild(errorElement);
        }

        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }

    // Скрыть ошибку для конкретного поля
    hideFieldError(input) {
        const errorElement = input.parentElement.querySelector('.invalid-feedback');
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }

    // Валидация всех полей спецификаций
    validateAllSpecifications() {
        const specInputs = this.specificationsSectionTarget.querySelectorAll('.spec-input');
        let isValid = true;

        specInputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        return isValid;
    }

    // Валидация всех обязательных полей формы
    validateRequiredFields() {
        let isValid = true;

        // Основные поля формы
        const requiredFields = this.formTarget.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                this.showFieldError(field, trans('inventory_item.validation.required'));
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
                this.hideFieldError(field);
            }
        });

        // Спецификации
        if (this.specificationsSectionTarget.querySelector('.spec-input')) {
            if (!this.validateAllSpecifications()) {
                isValid = false;
            }
        }

        return isValid;
    }

    // Обработка отправки формы
    async submitForm(event) {
        event.preventDefault();
        event.stopPropagation();

        // Скрываем предыдущие ошибки
        this.hideError();

        // Валидация
        if (!this.validateRequiredFields()) {
            this.showError(trans('inventory_item.validation.fill_required_fields'));
            return false;
        }

        // Подготовка данных формы
        const formData = new FormData(this.formTarget);

        // Показываем индикатор загрузки
        this.showLoading();

        try {
            const url = this.itemIdValue ? this.updateUrlValue : this.createUrlValue;
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.handleSuccess(data);
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
        if (this.submitButtonTarget) {
            const originalText = this.submitButtonTarget.innerHTML;
            this.submitButtonTarget.setAttribute('data-original-text', originalText);
            this.submitButtonTarget.innerHTML = '<span class="spinner-border spinner-border-sm"></span> ' + trans('common.messages.saving');
            this.submitButtonTarget.disabled = true;
        }
    }

    // Скрыть индикатор загрузки
    hideLoading() {
        if (this.submitButtonTarget) {
            const originalText = this.submitButtonTarget.getAttribute('data-original-text');
            if (originalText) {
                this.submitButtonTarget.innerHTML = originalText;
            } else {
                this.submitButtonTarget.innerHTML = '<i class="bi bi-check-circle"></i> ' + trans('common.actions.save');
            }
            this.submitButtonTarget.disabled = false;
        }
    }

    // Обработка успешного сохранения
    handleSuccess(data) {
        // Обновляем текущие спецификации
        this.specifications = data.specifications || {};

        // Показываем модальное окно успеха
        const successModalElement = document.getElementById('successModal');
        if (successModalElement) {
            const successModal = new Modal(successModalElement);
            const messageElement = document.getElementById('successModalMsg');
            if (messageElement) {
                messageElement.innerHTML = data.message;
            }
            successModal.show();

            // Если есть redirect URL, перенаправляем после закрытия модалки
            if (data.redirectUrl) {
                successModalElement.addEventListener('hidden.bs.modal', () => {
                    window.location.href = data.redirectUrl;
                });
            }
        }
    }

    // Обработка ошибок
    handleError(data) {
        if (data.errors) {
            this.displayValidationErrors(data.errors);
        } else {
            this.showError(data.message || trans('common.modals.saving_error'));
        }
    }

    // Обработка сетевой ошибки
    handleNetworkError() {
        this.showError(trans('common.modals.network_error'));
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
            });
        } else {
            errorHtml += `<li>${errors}</li>`;
        }

        errorHtml += '</ul>';

        this.showError('Ошибки валидации:', errorHtml);

        // Подсвечиваем проблемные поля
        if (typeof errors === 'object') {
            Object.keys(errors).forEach(field => {
                const input = this.formTarget.querySelector(`[name*="${field}"]`);
                if (input) {
                    input.classList.add('is-invalid');
                    this.showFieldError(input, errors[field]);
                }
            });
        }
    }

    // Показать общую ошибку формы
    showError(title, message = '') {
        if (this.formErrorsTarget) {
            this.formErrorsTarget.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <div>
                        <strong>${title}</strong>
                        ${message}
                    </div>
                </div>
            `;
            this.formErrorsTarget.classList.remove('d-none');

            // Прокрутка к ошибке
            this.formErrorsTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // Скрыть общую ошибку формы
    hideError() {
        if (this.formErrorsTarget) {
            this.formErrorsTarget.classList.add('d-none');
            this.formErrorsTarget.innerHTML = '';

            // Убираем индикаторы ошибок у основных полей
            this.formTarget.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
                this.hideFieldError(el);
            });
        }
    }
}
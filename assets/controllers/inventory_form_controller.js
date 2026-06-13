import BaseFormController from './base_form_controller';
import { trans } from '../translator';

export default class extends BaseFormController {
    static values = {
        aidaUrl: String,
        categorySpecsUrl: String,
        currentCategory: String,
        currentSpecifications: { type: String, default: '{}' }
    }

    static targets = [
        ...BaseFormController.targets,
        'inventoryNumberField',
        'inventoryNumberLabel',
        'categorySelector',
        'specificationsSection',
        'balanceType',
        'aidaFileInput',
        'progressBar',
        'parseResult',
        'specificationsField',
    ]

    connect() {
        // Парсим сохранённые спецификации
        this.parseSpecifications();

        // Начальное состояние инвентарного номера в зависимости от balanceType
        if (this.hasBalanceTypeTarget && this.hasInventoryNumberFieldTarget) {
            const value = this.getCurrentBalanceTypeValue();
            if (value !== null) {
                this.setBalanceTypeState(value);
            }
        }

        // Если уже выбрана категория – подгружаем спецификации
        if (this.currentCategoryValue && this.currentCategoryValue !== '') {
            this.loadCategorySpecifications(this.currentCategoryValue);
        }

        // Вешаем слушатель на submit формы для финальной валидации
        if (this.hasFormTarget) {
            this.formTarget.addEventListener('submit', (event) => {
                this.collectSpecifications();
                if (!this.validateForm()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            });
        }
    }

    getCurrentBalanceTypeValue() {
        // Ищем выбранный элемент (для радиокнопок)
        const checked = this.balanceTypeTargets.find(el => el.checked);
        if (checked) return checked.value;
        // Если один элемент и это select
        if (this.balanceTypeTargets.length === 1 && this.balanceTypeTargets[0].tagName === 'SELECT') {
            return this.balanceTypeTargets[0].value;
        }
        return null;
    }

    parseSpecifications() {
        let specs = this.currentSpecificationsValue;
        if (typeof specs === 'string') {
            try { specs = JSON.parse(specs); } catch { specs = {}; }
        }
        this.specifications = (Array.isArray(specs) || !specs) ? {} : specs;
    }

    onBalanceTypeChange(event) {
        this.setBalanceTypeState(event.target.value);
    }

    setBalanceTypeState(value) {
        const isOnBalance = value === 'on_balance';
        const field = this.inventoryNumberFieldTarget;
        const label = this.inventoryNumberLabelTarget;

        if (isOnBalance) {
            field.removeAttribute('disabled');
            field.setAttribute('required', 'required');
            field.setAttribute('aria-required', 'true');
            field.classList.add('required-field');
            label?.classList.add('required');
        } else {
            field.value = '';
            field.setAttribute('disabled', 'disabled');
            field.removeAttribute('required');
            field.removeAttribute('aria-required');
            field.classList.remove('required-field');
            label?.classList.remove('required');
            this.hideFieldError(field);
        }
    }
    // Обработчик изменения категории
    async onCategoryChange(event) {
        const newCategory = event.target.value;

        if (newCategory === this.currentCategoryValue) return;

        // Показываем индикатор загрузки
        this.specificationsSectionTarget.innerHTML = this.loadingTemplate();

        // Загружаем спецификации для новой категории
        await this.loadCategorySpecifications(newCategory);
    }

    // Загрузка спецификаций категории
    async loadCategorySpecifications(category) {
        const url = this.categorySpecsUrlValue.replace('__CATEGORY__', category);

        await this.fetchWithFeedback(url, {
            target: this.specificationsSectionTarget,
            errorKey: 'inventory_item.specifications.load_error',
            onSuccess: (data) => {
                this.currentCategoryValue = category;
                this.restoreSpecificationValues();
                this.initializeSpecValidation();
            }
        });
    }

    restoreSpecificationValues() {
        if (!this.specifications) return;

        Object.entries(this.specifications).forEach(([key, value]) => {
            const input = document.getElementById(`spec_${key}`);
            if (input) input.value = value;
        });
    }

    // Инициализация валидации спецификаций
    initializeSpecValidation() {
        this.specificationsSectionTarget
            .querySelectorAll('.spec-input')
            .forEach(input => {
                input.addEventListener('blur', (e) => this.validateSpecField(e));
                this.validateField(input);
            });
    }

    // Валидация поля спецификации (через событие)
    validateSpecField(event) {
        this.validateField(event.target);
    }

    // Валидация конкретного поля
    validateField(input) {
        const isRequired = this.isFieldRequired(input);
        const isValid = !isRequired || input.value.trim();

        if (!isValid) {
            input.classList.add('is-invalid');
            this.showFieldError(input, trans('form.validation.required'));
            return false;
        }

        input.classList.remove('is-invalid');
        this.hideFieldError(input);
        return true;
    }

    // Проверка, является ли поле обязательным
    isFieldRequired(input) {
        return input.hasAttribute('required') ||
            input.closest('.col-md-6, .col-12')?.querySelector('.required') !== null;
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
        const inputs = this.specificationsSectionTarget?.querySelectorAll('.spec-input') || [];
        return Array.from(inputs).reduce((valid, input) => this.validateField(input) && valid, true);
    }

    /**
     * Финальная валидация формы перед отправкой.
     * Возвращает false, если есть ошибки, и предотвращает отправку.
     */
    validateForm() {
        let isValid = true;

        // Проверяем все обязательные поля формы
        const requiredFields = this.formTarget?.querySelectorAll('[required]') || [];
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                this.showFieldError(field, trans('form.validation.required'));
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
                this.hideFieldError(field);
            }
        });

        // Валидация спецификаций
        if (isValid && this.specificationsSectionTarget?.querySelector('.spec-input')) {
            isValid = this.validateAllSpecifications() && isValid;
        }

        return isValid;
    }

    async uploadAidaReport(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Показываем прогресс
        this.progressBarTarget.classList.remove('d-none');
        this.parseResultTarget.classList.add('d-none');

        const formData = new FormData();
        formData.append('aida_report', file);

        try {
            const response = await fetch(this.aidaUrlValue, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Ошибка при разборе отчёта');
            }

            const data = await response.json();
            this.fillFormWithAidaData(data);

            this.parseResultTarget.classList.remove('d-none');
            this.parseResultTarget.innerHTML = `
                <i class="bi bi-check-circle"></i> Данные успешно загружены.
                Заполнены: процессор, ОЗУ, накопители, видеокарта и другие спецификации.
            `;
            setTimeout(() => this.parseResultTarget.classList.add('d-none'), 5000);
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.progressBarTarget.classList.add('d-none');
        }
    }

    fillFormWithAidaData(data) {
        // Предполагаем, что категория "Компьютер" имеет код 'computer'
        const categorySelect = this.element.querySelector('#inventory_item_category');
        if (categorySelect && categorySelect.value !== 'computer') {
            // Если категория не компьютер, можно либо сменить, либо показать сообщение
            categorySelect.value = 'computer';
            // Триггерим событие change, чтобы подгрузились спецификации для категории
            categorySelect.dispatchEvent(new Event('change'));
        }

        // Ждём, пока подгрузятся поля спецификаций (если есть задержка)
        setTimeout(() => this.fillSpecifications(data), 300);
    }

    fillSpecifications(data) {
        // Маппинг данных из парсера в ключи спецификаций
        const mapping = {
            'processor': data.cpu || '',
            'ram': data.ram_modules ? data.ram_modules.map(m => `${m.manufacturer} ${m.size} ${m.type} (${m.slot})`).join('; ') : '',
            'storage': data.storage_devices ? data.storage_devices.map(d => `${d.model} (${d.capacity})`).join('; ') : '',
            'graphics': data.gpu || '',
            'motherboard': data.motherboard || '',
            'os': data.os || '',
            'other': data.network_address || ''
        };

        // Обходим все input'ы спецификаций
        const specInputs = this.element.querySelectorAll('.spec-input');
        specInputs.forEach(input => {
            const fieldName = input.id.replace('spec_', '');
            if (mapping[fieldName] !== undefined) {
                input.value = mapping[fieldName];
                this.validateField(input);
            }
        });
    }

    loadingTemplate() {
        return `<div class="text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Загрузка...</span>
            </div>
            <p class="mt-2">${trans('inventory_item.specifications.loading')}</p>
        </div>`;
    }

    /**
     * Собирает все значения полей спецификаций в объект,
     * преобразует в JSON и записывает в скрытое поле формы.
     */
    collectSpecifications() {
        const specData = {};
        this.element.querySelectorAll('.spec-input').forEach(input => {
            const key = input.getAttribute('data-spec-key');
            if (key && input.value.trim()) {
                specData[key] = input.value.trim();
            }
        });
        if (this.hasSpecificationsFieldTarget) {
            this.specificationsFieldTarget.value = JSON.stringify(specData);
        }
    }
}
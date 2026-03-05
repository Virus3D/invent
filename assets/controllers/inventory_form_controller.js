import BaseFormController from './base_form_controller';
import { trans } from '../translator';

export default class extends BaseFormController {
    static values = {
        ...BaseFormController.values,
        categorySpecsUrl: String,
        updateUrl: String,
        createUrl: String,
        itemId: { type: Number, default: 0 },
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
    ]

    connect() {
        this.parseSpecifications();
        // Применяем начальное состояние инвентарного номера
        if (this.hasBalanceTypeTarget && this.hasInventoryNumberFieldTarget) {
            const value = this.getCurrentBalanceTypeValue();
            if (value !== null) {
                this.setBalanceTypeState(value);
            }
        }

        // Если при загрузке уже есть категория, убедимся что спецификации загружены
        if (this.currentCategoryValue && this.currentCategoryValue !== '') {
            this.loadCategorySpecifications(this.currentCategoryValue);
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
        return Array.from(
            this.specificationsSectionTarget.querySelectorAll('.spec-input')
        ).reduce((valid, input) => this.validateField(input) && valid, true);
    }

    // Валидация всех обязательных полей формы
    validateForm() {
        let isValid = true;

        // Валидация основных полей
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

    // Обработка отправки формы
    async submitForm(event) {
        const url = this.itemIdValue ? this.updateUrlValue : this.createUrlValue;
        return super.submitForm(event, { url });
    }

    // Обработка успешного сохранения
    handleSuccess(data) {
        // Обновляем текущие спецификации
        this.specifications = data.specifications || {};

        return this.handleSuccessResponse(data);
    }
}
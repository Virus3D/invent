import BaseFormController from './base_form_controller';
import { trans } from '../translator';

export default class extends BaseFormController {
    static values = {
        ...BaseFormController.values,
        currentLocationId: { type: String, default: '' }
    }

    static targets = [
        ...BaseFormController.targets,
        'fromLocation',
        'toLocation',
        'movedBy'
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
        const valid = !!this.toLocationTarget?.value;
        if (!valid) {
            this.showFieldError(this.toLocationTarget, trans('form.validation.to_location_required'));
        } else {
            this.hideFieldError(this.toLocationTarget);
        }
        return valid;
    }

    // Валидация имени перемещающего
    validateMovedBy() {
        const valid = !!this.movedByTarget?.value.trim();
        if (!valid) {
            this.showFieldError(this.movedByTarget, trans('form.validation.moved_by_required'));
        } else {
            this.hideFieldError(this.movedByTarget);
        }
        return valid;
    }


    // Валидация всей формы
    validateForm() {
        return this.validateToLocation() && this.validateMovedBy();
    }

    // Обработка отправки формы
    async submitForm(event) {
        const success = await super.submitForm(event);
        if (success) {
            this.saveMovedBy();
        }
        return success;
    }
}
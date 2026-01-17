import { Controller } from '@hotwired/stimulus';
import flatpickr from 'flatpickr';
import { Russian } from 'flatpickr/dist/l10n/ru.js';

export default class extends Controller {
    static values = {
        options: { type: Object, default: {} }
    }

    connect() {
        console.log('Flatpickr controller connected to:', this.element);

        try {
            const defaultOptions = {
                dateFormat: 'd.m.Y',
                locale: Russian,
                allowInput: true,
                wrap: true,
                disableMobile: true,
                onChange: (selectedDates, dateStr, instance) => {
                    instance.element.dispatchEvent(new Event('change', { bubbles: true }));
                }
            };

            // Объединяем дефолтные опции с переданными через data-атрибуты
            const options = {
                ...defaultOptions,
                ...this.optionsValue
            };

            this.flatpickrInstance = flatpickr(this.element, options);
            console.log('Flatpickr успешно инициализирован');
        } catch (error) {
            console.error('Ошибка при инициализации Flatpickr:', error);
        }
    }

    disconnect() {
        if (this.flatpickrInstance) {
            this.flatpickrInstance.destroy();
            this.flatpickrInstance = null;
        }
    }
}
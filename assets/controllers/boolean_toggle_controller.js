import { Controller } from '@hotwired/stimulus';

/**
 * Обрабатывает клик по чекбоксам с data-toggle-url,
 * отправляя AJAX-запрос к EasyAdmin.
 */
export default class extends Controller {
    static targets = ['input'];

    /**
     * @param {Event} event
     */
    async toggle(event) {
        const input = event.target;
        const url = input.dataset.toggleUrl;
        if (!url) return;

        const originalChecked = input.checked;
        const newValueParam = originalChecked ? 'true' : 'false';

        const urlObj = new URL(url, window.location.href);
        urlObj.searchParams.set('newValue', newValueParam);
        const fullUrl = urlObj.toString();
        try {
            const response = await fetch(fullUrl, {
                method: 'PATCH',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error('Network error');

            const result = await response.text();
            input.checked = result.trim() === '1';
        } catch (error) {
            // Возвращаем исходное состояние при ошибке
            input.checked = !originalChecked;
        }
    }
}
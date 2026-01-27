import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        toggleUrl: String,
        resetUrl: String,
    };

    async toggle(event) {
        const checkbox = event.currentTarget;
        const url = checkbox.dataset.materialCheckToggleUrlValue || this.toggleUrlValue;

        if (!url) {
            return;
        }

        const previous = checkbox.checked;

        try {
            const formData = new FormData();
            formData.append('checked', checkbox.checked ? '1' : '0');

            const response = await fetch(url, {
                method: 'POST',
                body: formData,
            });

            if (!response.ok) {
                throw new Error('Request failed');
            }
        } catch (e) {
            checkbox.checked = !previous;
            console.error(e);
        }
    }

    async resetAll() {
        const url = this.resetUrlValue || this.element.dataset.materialCheckResetUrlValue;

        if (!url) {
            return;
        }

        try {
            const response = await fetch(url, { method: 'POST' });
            if (!response.ok) {
                throw new Error('Request failed');
            }

            document.querySelectorAll('input[data-controller~="material-check"]').forEach((checkbox) => {
                checkbox.checked = false;
            });
        } catch (e) {
            console.error(e);
        }
    }
}

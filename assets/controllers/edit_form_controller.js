import BaseFormController from './base_form_controller';

export default class extends BaseFormController {
    static values = {
        ...BaseFormController.values,
        url: String
    }

    // Обработка отправки формы
    async submitForm(event) {
        return super.submitForm(event, { url: this.urlValue });
    }
}
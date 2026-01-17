import { startStimulusApp } from '@symfony/stimulus-bridge';
import { Tooltip } from 'bootstrap';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));

// Register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
document.addEventListener('DOMContentLoaded', function () {
    // Инициализация подсказок
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new Tooltip(tooltipTriggerEl);
    });
});
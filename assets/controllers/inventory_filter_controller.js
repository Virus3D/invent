import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'container', 'loading', 'count', 'stats'];
    static values = {
        url: String,
        locationId: Number
    };

    connect() {
        console.log('Filter controller connected');

        // Авто-фильтрация при изменении полей с задержкой
        this.formTarget.querySelectorAll('input[type="text"]').forEach(input => {
            input.addEventListener('input', this.debounce(() => this.filter(), 500));
        });
    }

    filter(event = null) {
        if (event) {
            event.preventDefault();
        }

        this.showLoading();

        const formData = new FormData(this.formTarget);
        const params = new URLSearchParams(formData);

        // Добавляем параметр location_id для фильтрации по локации
        params.append('location_id', this.locationIdValue);

        fetch(`${this.urlValue}?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                this.updateContent(data);
            })
            .catch(error => {
                console.error('Error:', error);
                this.showError();
            })
            .finally(() => {
                this.hideLoading();
            });
    }

    reset(event) {
        if (event) {
            event.preventDefault();
        }

        // Сброс формы
        this.formTarget.reset();

        // Сброс Select2
        if (typeof $.fn.select2 !== 'undefined') {
            $(this.formTarget).find('select').val(null).trigger('change');
        }

        // Выполняем фильтрацию с пустыми параметрами
        this.filter();
    }

    updateContent(data) {
        if (this.hasContainerTarget) {
            this.containerTarget.innerHTML = data.html || '';
        }

        if (this.hasCountTarget) {
            this.countTarget.textContent = data.count || 0;
        }

        // Обновляем текст с количеством показанных объектов
        const shownText = document.getElementById('shown-objects-text');
        if (shownText) {
            shownText.textContent = `Показано объектов: ${data.count || 0}`;
        }
    }

    showLoading() {
        if (this.hasLoadingTarget) {
            this.loadingTarget.style.display = 'flex';
        }

        // Можно добавить анимацию затемнения
        if (this.hasContainerTarget) {
            this.containerTarget.style.opacity = '0.5';
        }
    }

    hideLoading() {
        if (this.hasLoadingTarget) {
            this.loadingTarget.style.display = 'none';
        }

        if (this.hasContainerTarget) {
            this.containerTarget.style.opacity = '1';
        }
    }

    showError() {
        if (this.hasContainerTarget) {
            this.containerTarget.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    Произошла ошибка при загрузке данных. Пожалуйста, попробуйте еще раз.
                </div>
            `;
        }
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}
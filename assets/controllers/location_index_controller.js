import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';
import { trans } from '../translator';

export default class extends Controller {
    static values = {
        csrfToken: String,
        apiUrls: { type: Object, default: {} },
        sortField: { type: String, default: '' },
        sortOrder: { type: String, default: 'asc' },
        searchQuery: { type: String, default: '' }
    }

    static targets = [
        'searchInput',
        'searchButton',
        'selectAll',
        'locationCheckbox',
        'massMoveButton',
        'massDeleteButton',
        'selectedCount',
        'exportButton',
        'showEmptyCheckbox',
        'deleteButton',
        'tableBody',
        'statsContainer',
        'errorContainer'
    ]

    static classes = ['selected', 'hidden']

    connect() {
        // Инициализируем состояния
        this.selectedIds = new Set();

        // Инициализируем компоненты
        this.initializeComponents();

        // Восстанавливаем состояние из localStorage
        this.restoreState();
    }

    // Инициализация компонентов
    initializeComponents() {
        this.initializeSorting();
        this.initializeCheckboxes();
    }

    // Инициализация сортировки
    initializeSorting() {
        this.element.querySelectorAll('.sort-link').forEach(link => {
            link.addEventListener('click', (e) => this.handleSort(e));
        });
    }

    // Инициализация чекбоксов
    initializeCheckboxes() {
        if (this.hasSelectAllTarget) {
            this.selectAllTarget.addEventListener('change', (e) => this.toggleAllLocations(e));
        }
    }

    // Восстановление состояния
    restoreState() {
        // Восстанавливаем состояние чекбокса "Показывать пустые"
        if (this.hasShowEmptyCheckboxTarget) {
            const showEmpty = localStorage.getItem('location_show_empty') === 'true';
            this.showEmptyCheckboxTarget.checked = showEmpty;
            this.toggleEmptyLocations(showEmpty);
        }
    }

    // Обработка поиска
    handleSearch() {
        const query = this.searchInputTarget.value.trim();
        if (query) {
            const url = new URL(window.location.href);
            url.searchParams.set('q', query);
            url.searchParams.delete('page'); // Сбрасываем пагинацию
            window.location.href = url.toString();
        } else {
            window.location.href = window.location.pathname;
        }
    }

    // Быстрый поиск по таблице
    quickSearch(event) {
        const query = event.target.value.toLowerCase();
        const rows = this.tableBodyTarget.querySelectorAll('tr[data-id]');

        rows.forEach(row => {
            const name = row.dataset.name?.toLowerCase() || '';
            const room = row.dataset.room?.toLowerCase() || '';
            const matches = name.includes(query) || room.includes(query);
            row.style.display = matches ? '' : 'none';
        });
    }

    // Обработка сортировки
    handleSort(event) {
        event.preventDefault();
        const sortField = event.currentTarget.dataset.sort;
        const currentUrl = new URL(window.location.href);
        const currentSort = currentUrl.searchParams.get('sort');
        const currentOrder = currentUrl.searchParams.get('order');

        let newOrder = 'asc';
        if (currentSort === sortField && currentOrder === 'asc') {
            newOrder = 'desc';
        }

        currentUrl.searchParams.set('sort', sortField);
        currentUrl.searchParams.set('order', newOrder);
        window.location.href = currentUrl.toString();
    }

    // Выбор/отмена всех локаций
    toggleAllLocations(event) {
        const isChecked = event.target.checked;
        this.locationCheckboxTargets.forEach(checkbox => {
            checkbox.checked = isChecked;
            const fakeEvent = { target: checkbox };
            this.toggleLocationSelection(fakeEvent);
        });
        this.updateSelectionState();
    }

    // Переключение выбора локации
    toggleLocationSelection(event) {
        const checkbox = event.target;
        const id = checkbox.value;

        if (checkbox.checked) {
            this.selectedIds.add(id);
            const row = this.findRowForCheckbox(checkbox);
            if (row) {
                row.classList.add('table-active');
            }
        } else {
            this.selectedIds.delete(id);
            const row = this.findRowForCheckbox(checkbox);
            if (row) {
                row.classList.remove('table-active');
            }
        }

        this.updateSelectionState();
    }

    // Поиск строки для чекбокса
    findRowForCheckbox(checkbox) {
        // Ищем родительскую строку таблицы
        let element = checkbox;
        while (element && element.tagName !== 'TR') {
            element = element.parentElement;
        }
        return element;
    }

    // Обновление состояния выбора
    updateSelectionState() {
        const selectedCount = this.selectedIds.size;

        if (this.hasSelectedCountTarget) {
            this.selectedCountTarget.textContent = selectedCount;

            if (selectedCount > 0) {
                this.selectedCountTarget.classList.remove('bg-primary');
                this.selectedCountTarget.classList.add('bg-success');
            } else {
                this.selectedCountTarget.classList.remove('bg-success');
                this.selectedCountTarget.classList.add('bg-primary');
            }
        }

        if (this.hasMassMoveButtonTarget) {
            this.massMoveButtonTarget.disabled = selectedCount === 0;
        }

        if (this.hasMassDeleteButtonTarget) {
            this.massDeleteButtonTarget.disabled = selectedCount === 0;
        }

        // Обновляем состояние "Выбрать все"
        if (this.hasSelectAllTarget) {
            const totalCheckboxes = this.locationCheckboxTargets.length;
            const checkedCount = Array.from(this.locationCheckboxTargets)
                .filter(cb => cb.checked).length;

            this.selectAllTarget.checked = checkedCount === totalCheckboxes && totalCheckboxes > 0;
            this.selectAllTarget.indeterminate = checkedCount > 0 && checkedCount < totalCheckboxes;
        }
    }

    // Показать/скрыть пустые локации
    toggleEmptyLocations(show = null) {
        const showEmpty = show !== null ? show : this.showEmptyCheckboxTarget.checked;

        // Сохраняем в localStorage
        localStorage.setItem('location_show_empty', showEmpty);

        const rows = this.tableBodyTarget.querySelectorAll('tr[data-id]');
        rows.forEach(row => {
            const objectCount = parseInt(row.querySelector('.location-object-count')?.textContent || 0);
            if (objectCount === 0) {
                row.style.display = showEmpty ? '' : 'none';
            }
        });
    }

    // Массовое удаление
    async handleMassDelete() {
        if (this.selectedIds.size === 0) return;

        const confirmed = await this.showConfirmation({
            title: trans('location.index.mass_delete'),
            message: trans('location.index.mass_delete_confirm', { count: this.selectedIds.size }),
            icon: 'bi-exclamation-triangle text-warning'
        });

        if (!confirmed) return;

        try {
            await this.performMassDelete();
            this.showSuccess(trans('location.delete.success', { '%count%': this.selectedIds.size }));
            setTimeout(() => window.location.reload(), 1500);
        } catch (error) {
            this.showError(trans('location.delete.error'));
        }
    }

    // Выполнение массового удаления
    async performMassDelete() {
        const response = await fetch(this.apiUrlsValue.massDelete, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.csrfTokenValue
            },
            body: JSON.stringify({ ids: Array.from(this.selectedIds) })
        });

        if (!response.ok) throw new Error('Delete failed');
        return response.json();
    }

    // Массовое перемещение
    async handleMassMove() {
        if (this.selectedIds.size === 0) return;

        const targetLocation = await this.showMoveModal();
        if (!targetLocation) return;

        try {
            await this.performMassMove(targetLocation);
            this.showSuccess(this.t('move_success', { count: this.selectedIds.size }));
            setTimeout(() => window.location.reload(), 1500);
        } catch (error) {
            this.showError(this.t('move_error'));
        }
    }

    // Показать модальное окно перемещения
    async showMoveModal() {
        return new Promise((resolve) => {
            // Создаем динамическое модальное окно
            const modalHtml = `
                <div class="modal fade" id="moveModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${this.t('mass_move')}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">${this.t('select_target_location')}</label>
                                    <select class="form-select" id="targetLocationSelect">
                                        <option value="">${this.t('select_target_location')}</option>
                                        <option value="none">${this.t('without_location')}</option>
                                        <!-- Опции будут добавлены динамически -->
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">${this.t('reason')}</label>
                                    <textarea class="form-control" id="moveReason" rows="3"
                                              placeholder="${this.t('enter_move_reason')}"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    ${this.t('cancel')}
                                </button>
                                <button type="button" class="btn btn-primary" id="confirmMove">
                                    ${this.t('confirm')}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Добавляем модальное окно в DOM
            const modalContainer = document.createElement('div');
            modalContainer.innerHTML = modalHtml;
            document.body.appendChild(modalContainer);

            // Загружаем доступные локации
            this.loadAvailableLocations(modalContainer);

            // Показываем модальное окно
            const moveModal = new Modal(modalContainer.querySelector('#moveModal'));
            moveModal.show();

            // Обработка подтверждения
            modalContainer.querySelector('#confirmMove').addEventListener('click', () => {
                const targetLocationId = modalContainer.querySelector('#targetLocationSelect').value;
                const reason = modalContainer.querySelector('#moveReason').value;

                if (!targetLocationId) {
                    alert(this.t('select_target_location'));
                    return;
                }

                moveModal.hide();
                resolve({ targetLocationId, reason });

                // Удаляем модальное окно из DOM
                setTimeout(() => modalContainer.remove(), 300);
            });

            // Обработка закрытия
            moveModal._element.addEventListener('hidden.bs.modal', () => {
                resolve(null);
                setTimeout(() => modalContainer.remove(), 300);
            });
        });
    }

    // Загрузка доступных локаций
    async loadAvailableLocations(modalContainer) {
        try {
            const response = await fetch(this.apiUrlsValue.availableLocations || '/api/locations/available');
            const data = await response.json();

            const select = modalContainer.querySelector('#targetLocationSelect');
            data.locations.forEach(location => {
                const option = document.createElement('option');
                option.value = location.id;
                option.textContent = `${location.name} (${location.roomNumber || 'без номера'})`;
                select.appendChild(option);
            });
        } catch (error) {
            console.error('Failed to load locations:', error);
        }
    }

    // Выполнение массового перемещения
    async performMassMove({ targetLocationId, reason }) {
        const response = await fetch(this.apiUrlsValue.massMove || '/api/objects/mass-move', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.csrfTokenValue
            },
            body: JSON.stringify({
                sourceLocationIds: Array.from(this.selectedIds),
                targetLocationId,
                reason
            })
        });

        if (!response.ok) throw new Error('Move failed');
        return response.json();
    }

    // Экспорт данных
    async handleExport() {
        try {
            const response = await fetch(this.apiUrlsValue.export || '/api/locations/export', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfTokenValue
                },
                body: JSON.stringify({
                    ids: this.selectedIds.size > 0 ? Array.from(this.selectedIds) : 'all',
                    format: 'csv'
                })
            });

            if (!response.ok) throw new Error('Export failed');

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `locations_export_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            this.showSuccess(this.t('export_success'));
        } catch (error) {
            this.showError(this.t('export_error'));
        }
    }

    // Удаление одиночной локации
    async handleDelete(event) {
        const button = event.currentTarget;
        const locationId = button.dataset.id;
        const locationName = button.dataset.name;

        const confirmed = await this.showConfirmationModal(
            this.t('delete_confirm'),
            `${this.t('delete_warning')}: ${locationName}`
        );

        if (!confirmed) return;

        try {
            await this.performDelete(locationId);
            this.showSuccess(this.t('delete_success_single', { name: locationName }));
            setTimeout(() => window.location.reload(), 1500);
        } catch (error) {
            this.showError(this.t('delete_error'));
        }
    }

    // Выполнение удаления
    async performDelete(locationId) {
        const response = await fetch(`/api/location/${locationId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-Token': this.csrfTokenValue
            }
        });

        if (!response.ok) throw new Error('Delete failed');
        return response.json();
    }

    // Загрузка объектов в локации
    async loadLocationObjects(locationId) {
        try {
            const response = await fetch(`/api/location/${locationId}/objects`);
            const data = await response.json();

            return data.objects || [];
        } catch (error) {
            console.error('Failed to load objects:', error);
            return [];
        }
    }

    // Показать модальное окно подтверждения
    showConfirmationModal(title, message) {
        return new Promise((resolve) => {
            const modalHtml = `
                <div class="modal fade" id="confirmationModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${title}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>${message}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    ${this.t('cancel')}
                                </button>
                                <button type="button" class="btn btn-danger" id="confirmAction">
                                    ${this.t('confirm')}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const modalContainer = document.createElement('div');
            modalContainer.innerHTML = modalHtml;
            document.body.appendChild(modalContainer);

            const modal = new Modal(modalContainer.querySelector('#confirmationModal'));
            modal.show();

            modalContainer.querySelector('#confirmAction').addEventListener('click', () => {
                modal.hide();
                resolve(true);
                setTimeout(() => modalContainer.remove(), 300);
            });

            modal._element.addEventListener('hidden.bs.modal', () => {
                resolve(false);
                setTimeout(() => modalContainer.remove(), 300);
            });
        });
    }

    // Показать успешное сообщение
    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    // Показать ошибку
    showError(message) {
        this.showNotification(message, 'danger');
    }

    // Показать уведомление
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        notification.style.zIndex = '9999';
        notification.style.maxWidth = '400px';

        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                <div class="flex-grow-1">${message}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    // Сброс выбора
    resetSelection() {
        this.selectedIds.clear();
        this.locationCheckboxTargets.forEach(checkbox => {
            checkbox.checked = false;
            checkbox.closest('tr')?.classList.remove('table-active');
        });
        this.updateSelectionState();
    }

    // Отключение контроллера
    disconnect() {
        // Сохраняем состояние перед уходом со страницы
        if (this.hasShowEmptyCheckboxTarget) {
            localStorage.setItem('location_show_empty', this.showEmptyCheckboxTarget.checked);
        }
    }
}
#!/usr/bin/env bash
#
# Скрипт обновления проекта Garden с GitHub
# Использование: ./scripts/update.sh [--force] [--skip-migrations] [--skip-assets] [--branch=main]
#
# --force               - Принудительное обновление без подтверждения
# --skip-migrations     - Пропустить миграции БД
# --skip-assets         - Пропустить сборку фронтенд-ассетов
# --branch=main         - Указать ветку для обновления (по умолчанию main)
# --skip-cache          - Пропустить очистку кэша
# --skip-backup         - Пропустить создание бэкапа БД
# --no-interaction      - Отключить интерактивный режим
#

set -e  # Останавливаем скрипт при любой ошибке

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Конфигурация
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOG_FILE="$PROJECT_ROOT/var/log/update_$(date +%Y%m%d_%H%M%S).log"
BACKUP_DIR="$PROJECT_ROOT/var/backups"
ENV_FILE="$PROJECT_ROOT/.env.local"
PHP_BIN="php"
COMPOSER_BIN="composer"
NPM_BIN="npm"
YARN_BIN="yarn"
SUDO_USER_NAME="www-data"
APP_ENV="${APP_ENV:-prod}"
BRANCH="main"
FORCE=false
SKIP_MIGRATIONS=false
SKIP_ASSETS=false
SKIP_CACHE=false
SKIP_BACKUP=false
NO_INTERACTION=false

# Парсинг аргументов командной строки
while [[ $# -gt 0 ]]; do
    case $1 in
        --force)
            FORCE=true
            shift
            ;;
        --skip-migrations)
            SKIP_MIGRATIONS=true
            shift
            ;;
        --skip-assets)
            SKIP_ASSETS=true
            shift
            ;;
        --skip-cache)
            SKIP_CACHE=true
            shift
            ;;
        --skip-backup)
            SKIP_BACKUP=true
            shift
            ;;
        --branch=*)
            BRANCH="${1#*=}"
            shift
            ;;
        --no-interaction)
            NO_INTERACTION=true
            shift
            ;;
        --help)
            echo "Использование: $0 [--force] [--skip-migrations] [--skip-assets] [--branch=main] [--no-interaction]"
            echo ""
            echo "  --force               - Принудительное обновление без подтверждения"
            echo "  --skip-migrations     - Пропустить миграции БД"
            echo "  --skip-assets         - Пропустить сборку фронтенд-ассетов"
            echo "  --branch=main         - Указать ветку для обновления (по умолчанию main)"
            echo "  --skip-cache          - Пропустить очистку кэша"
            echo "  --skip-backup         - Пропустить создание бэкапа БД"
            echo "  --no-interaction      - Отключить интерактивный режим"
            exit 0
            ;;
        *)
            echo -e "${RED}Неизвестный аргумент: $1${NC}"
            echo "Используйте --help для справки"
            exit 1
            ;;
    esac
done

# Функция логирования
log() {
    local level="$1"
    local message="$2"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')

    case $level in
        "INFO")
            echo -e "${GREEN}[INFO]${NC} $message"
            ;;
        "WARN")
            echo -e "${YELLOW}[WARN]${NC} $message"
            ;;
        "ERROR")
            echo -e "${RED}[ERROR]${NC} $message" >&2
            ;;
        "STEP")
            echo -e "${BLUE}[STEP]${NC} $message"
            ;;
        *)
            echo "[$level] $message"
            ;;
    esac

    echo "[$timestamp] [$level] $message" >> "$LOG_FILE"
}

# Функция проверки ошибок
check_error() {
    if [ $? -ne 0 ]; then
        log "ERROR" "Ошибка выполнения команды: $1"
        log "ERROR" "Подробности смотрите в логе: $LOG_FILE"
        exit 1
    fi
}

# Функция проверки наличия команды
check_command() {
    if ! command -v "$1" &> /dev/null; then
        log "ERROR" "Команда '$1' не найдена. Пожалуйста, установите её."
        exit 1
    fi
}

# Функция для запуска команд от имени www-data
run_as_www_data() {
    sudo -u "$SUDO_USER_NAME" "$@"
}

# Функция проверки окружения
check_environment() {
    log "STEP" "Проверка окружения..."

    # Проверка PHP
    check_command "php"
    PHP_VERSION=$($PHP_BIN -v | head -n 1 | cut -d' ' -f2 | cut -d'.' -f1-2)
    if [[ "$PHP_VERSION" < "8.2" ]]; then
        log "WARN" "Рекомендуется PHP 8.2+, текущая версия: $PHP_VERSION"
    else
        log "INFO" "PHP версия: $PHP_VERSION ✓"
    fi

    # Проверка Composer
    check_command "composer"
    log "INFO" "Composer установлен ✓"

    # Проверка Node.js (если нужна сборка ассетов)
    if [ "$SKIP_ASSETS" = false ]; then
        if command -v "node" &> /dev/null; then
            NODE_VERSION=$(node -v)
            log "INFO" "Node.js версия: $NODE_VERSION ✓"
        else
            log "WARN" "Node.js не установлен. Сборка ассетов будет пропущена."
            SKIP_ASSETS=true
        fi
    fi

    # Проверка доступа к БД
    if [ -f "$ENV_FILE" ]; then
        source "$ENV_FILE"
        if [ -n "$DATABASE_URL" ]; then
            log "INFO" "Переменная DATABASE_URL установлена ✓"
        else
            log "WARN" "DATABASE_URL не найдена в .env.local"
        fi
    else
        log "WARN" "Файл .env.local не найден"
    fi

    # Проверка доступа к Git
    if ! git status &> /dev/null; then
        log "ERROR" "Не удаётся выполнить git. Убедитесь, что вы находитесь в репозитории."
        exit 1
    fi
    log "INFO" "Git доступен ✓"
}

# Функция создания бэкапа БД
backup_database() {
    if [ "$SKIP_BACKUP" = true ]; then
        log "WARN" "Создание бэкапа пропущено (--skip-backup)"
        return 0
    fi

    log "STEP" "Создание бэкапа базы данных..."

    mkdir -p "$BACKUP_DIR"
    local backup_file="$BACKUP_DIR/db_backup_$(date +%Y%m%d_%H%M%S).sql"

    # Извлекаем параметры БД из DATABASE_URL
    if [ -f "$ENV_FILE" ]; then
        source "$ENV_FILE"
    fi

    if [ -z "$DATABASE_URL" ]; then
        log "WARN" "DATABASE_URL не найдена. Пропускаем бэкап."
        return 0
    fi

    # Парсим DATABASE_URL
    local db_user=$(echo "$DATABASE_URL" | sed -n 's/.*:\/\/\([^:]*\):.*/\1/p')
    local db_pass=$(echo "$DATABASE_URL" | sed -n 's/.*:\/\/[^:]*:\([^@]*\)@.*/\1/p')
    local db_host=$(echo "$DATABASE_URL" | sed -n 's/.*@\([^:]*\):.*/\1/p')
    local db_port=$(echo "$DATABASE_URL" | sed -n 's/.*:\([0-9]*\)\/.*/\1/p')
    local db_name=$(echo "$DATABASE_URL" | sed -n 's/.*\/\([^?]*\).*/\1/p')

    if [ -z "$db_name" ]; then
        log "WARN" "Не удалось определить имя БД из DATABASE_URL. Пропускаем бэкап."
        return 0
    fi

    log "INFO" "Создание дампа БД $db_name..."

    if [ -n "$db_pass" ] && [ -n "$db_user" ]; then
        mysqldump -h "$db_host" -P "$db_port" -u "$db_user" -p"$db_pass" "$db_name" > "$backup_file" 2>> "$LOG_FILE"
    else
        mysqldump "$db_name" > "$backup_file" 2>> "$LOG_FILE"
    fi

    if [ $? -eq 0 ] && [ -f "$backup_file" ]; then
        local size=$(du -h "$backup_file" | cut -f1)
        log "INFO" "Бэкап создан: $backup_file ($size)"

        # Сжимаем бэкап
        gzip "$backup_file"
        log "INFO" "Бэкап сжат: $backup_file.gz"
    else
        log "WARN" "Не удалось создать бэкап БД"
    fi
}

# Функция обновления кода из Git
update_code() {
    log "STEP" "Обновление кода из GitHub..."

    # Проверяем текущую ветку
    current_branch=$(git branch --show-current)
    log "INFO" "Текущая ветка: $current_branch"

    if [ "$current_branch" != "$BRANCH" ]; then
        log "WARN" "Текущая ветка ($current_branch) отличается от целевой ($BRANCH)"
        if [ "$FORCE" = false ] && [ "$NO_INTERACTION" = false ]; then
            read -p "Переключиться на ветку $BRANCH? (y/n) " -n 1 -r
            echo
            if [[ ! $REPLY =~ ^[Yy]$ ]]; then
                log "ERROR" "Обновление отменено пользователем"
                exit 1
            fi
        fi
        git checkout "$BRANCH" >> "$LOG_FILE" 2>&1
        check_error "Переключение на ветку $BRANCH"
    fi

    # Сохраняем локальные изменения
    if ! git diff --quiet; then
        log "WARN" "Есть локальные изменения"
        if [ "$FORCE" = false ] && [ "$NO_INTERACTION" = false ]; then
            read -p "Сохранить локальные изменения? (y/n) " -n 1 -r
            echo
            if [[ $REPLY =~ ^[Yy]$ ]]; then
                local stash_name="auto_stash_$(date +%Y%m%d_%H%M%S)"
                git stash push -m "$stash_name" >> "$LOG_FILE" 2>&1
                log "INFO" "Локальные изменения сохранены в stash: $stash_name"
            fi
        fi
    fi

    # Определяем первый доступный remote
    REMOTE=$(git remote | head -n 1)
    if [ -z "$REMOTE" ]; then
        log "ERROR" "Не найден ни один удалённый репозиторий. Добавьте его через 'git remote add'."
        exit 1
    fi
    log "INFO" "Используется remote: $REMOTE"

    # Загрузка обновлений
    log "INFO" "Загрузка изменений из ветки $BRANCH..."
    git fetch "$REMOTE" "$BRANCH" >> "$LOG_FILE" 2>&1
    check_error "git fetch"

    # Показываем изменения
    local commits=$(git log HEAD.."$REMOTE"/"$BRANCH" --oneline | wc -l)
    if [ "$commits" -eq 0 ]; then
        log "INFO" "Нет новых изменений для загрузки"
        return 0
    fi

    log "INFO" "Найдено $commits новых коммитов:"
    git log HEAD.."$REMOTE"/"$BRANCH" --oneline --no-decorate | head -10 >> "$LOG_FILE"
    git log HEAD.."$REMOTE"/"$BRANCH" --oneline --no-decorate | head -10 | while read line; do
        log "INFO" "  $line"
    done

    if [ "$commits" -gt 10 ]; then
        log "INFO" "  ... и ещё $(($commits - 10)) коммитов"
    fi

    # Подтверждение
    if [ "$FORCE" = false ] && [ "$NO_INTERACTION" = false ]; then
        read -p "Применить изменения? (y/n) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log "ERROR" "Обновление отменено пользователем"
            exit 1
        fi
    fi

    # Применяем изменения
    git pull "$REMOTE" "$BRANCH" >> "$LOG_FILE" 2>&1
    check_error "git pull"

    log "INFO" "Код успешно обновлён"
}

# Функция обновления PHP-зависимостей
update_php_dependencies() {
    log "STEP" "Обновление PHP-зависимостей..."

    #$COMPOSER_BIN install --no-dev --optimize-autoloader --no-interaction >> "$LOG_FILE" 2>&1
    run_as_www_data $COMPOSER_BIN install --optimize-autoloader --no-interaction >> "$LOG_FILE" 2>&1
    check_error "composer install"

    log "INFO" "PHP-зависимости обновлены ✓"
}

# Функция обновления Node.js-зависимостей и сборки ассетов
update_assets() {
    if [ "$SKIP_ASSETS" = true ]; then
        log "WARN" "Сборка ассетов пропущена (--skip-assets)"
        return 0
    fi

    log "STEP" "Обновление фронтенд-ассетов..."

    if [ ! -f "package.json" ]; then
        log "WARN" "package.json не найден. Сборка ассетов пропущена."
        return 0
    fi

    # Выбираем менеджер пакетов
    if command -v "yarn" &> /dev/null && [ -f "yarn.lock" ]; then
        PKG_MANAGER="$YARN_BIN"
        INSTALL_CMD="install"
        BUILD_CMD="build"
        log "INFO" "Используется Yarn"
    elif command -v "npm" &> /dev/null; then
        PKG_MANAGER="$NPM_BIN"
        INSTALL_CMD="ci"
        BUILD_CMD="run build"
        log "INFO" "Используется npm"
    else
        log "WARN" "Менеджер пакетов не найден. Сборка ассетов пропущена."
        return 0
    fi

    # Устанавливаем зависимости
    log "INFO" "Установка фронтенд-зависимостей..."
    run_as_www_data $PKG_MANAGER $INSTALL_CMD >> "$LOG_FILE" 2>&1
    check_error "Установка npm-пакетов"

    # Собираем ассеты
    log "INFO" "Сборка фронтенд-ассетов..."
    run_as_www_data $PKG_MANAGER $BUILD_CMD >> "$LOG_FILE" 2>&1
    check_error "Сборка ассетов"

    log "INFO" "Фронтенд-ассеты собраны ✓"
}

# Функция выполнения миграций БД
run_migrations() {
    if [ "$SKIP_MIGRATIONS" = true ]; then
        log "WARN" "Миграции БД пропущены (--skip-migrations)"
        return 0
    fi

    log "STEP" "Выполнение миграций БД..."

    # Проверяем, есть ли новые миграции
    run_as_www_data $PHP_BIN bin/console doctrine:migrations:status --no-interaction >> "$LOG_FILE" 2>&1

    # Синхронизация метаданных
    log "INFO" "Синхронизация метаданных..."
    run_as_www_data $PHP_BIN bin/console doctrine:migrations:sync-metadata-storage --no-interaction >> "$LOG_FILE" 2>&1
    check_error "sync-metadata-storage"

    # Выполнение миграций
    log "INFO" "Применение миграций..."
    run_as_www_data $PHP_BIN bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration >> "$LOG_FILE" 2>&1

    local exit_code=$?
    if [ $exit_code -ne 0 ]; then
        log "ERROR" "Ошибка при выполнении миграций (код: $exit_code)"
        log "ERROR" "Подробности в логе: $LOG_FILE"

        if [ "$NO_INTERACTION" = false ]; then
            read -p "Продолжить обновление, несмотря на ошибку миграций? (y/n) " -n 1 -r
            echo
            if [[ ! $REPLY =~ ^[Yy]$ ]]; then
                exit 1
            fi
        else
            exit 1
        fi
    fi

    log "INFO" "Миграции выполнены ✓"
}

# Функция очистки кэша
clear_cache() {
    if [ "$SKIP_CACHE" = true ]; then
        log "WARN" "Очистка кэша пропущена (--skip-cache)"
        return 0
    fi

    log "STEP" "Очистка кэша..."

    # Очистка кэша Symfony
    run_as_www_data $PHP_BIN bin/console cache:clear --env="$APP_ENV" --no-debug --no-interaction >> "$LOG_FILE" 2>&1
    check_error "Очистка кэша"

    # Тёплый кэш (прекомпиляция)
    log "INFO" "Прогрев кэша..."
    run_as_www_data $PHP_BIN bin/console cache:warmup --env="$APP_ENV" --no-debug >> "$LOG_FILE" 2>&1
    check_error "Прогрев кэша"

    log "INFO" "Кэш очищен ✓"
}

# Функция остановки старых воркеров
stop_workers() {
    log "STEP" "Остановка старых воркеров..."

    run_as_www_data $PHP_BIN bin/console messenger:stop-workers --no-interaction >> "$LOG_FILE" 2>&1
    log "INFO" "Воркеры остановлены ✓"
}

# Функция проверки после обновления
post_update_check() {
    log "STEP" "Проверка после обновления..."

    # Проверка маршрутов
    log "INFO" "Проверка маршрутов..."
    run_as_www_data $PHP_BIN bin/console debug:router --no-interaction >> "$LOG_FILE" 2>&1 || true

    # Проверка конфигурации
    log "INFO" "Проверка конфигурации..."
    run_as_www_data $PHP_BIN bin/console debug:config --no-interaction >> "$LOG_FILE" 2>&1 || true

    log "INFO" "Проверка успешна ✓"
}

# Функция вывода финальной информации
print_summary() {
    log "STEP" "Обновление завершено успешно!"

    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}✅ Обновление завершено успешно!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    echo -e "📋 ${BLUE}Ветка:${NC} $BRANCH"
    echo -e "📋 ${BLUE}Окружение:${NC} $APP_ENV"
    echo -e "📋 ${BLUE}Лог:${NC} $LOG_FILE"
    echo ""
    echo -e "${YELLOW}Следующие шаги:${NC}"
    echo "  1. Проверьте статус воркеров: sudo supervisorctl status"
    echo "  2. Проверьте логи: tail -f var/log/prod.log"
    echo "  3. Проверьте работоспособность сайта"
    echo ""

    if [ -f "$LOG_FILE" ]; then
        echo -e "${YELLOW}Последние строки лога:${NC}"
        tail -5 "$LOG_FILE" | while read line; do
            echo "  $line"
        done
        echo ""
    fi

    echo -e "${YELLOW}⚠️  Важно:${NC}"
    echo "  - Перезапустите воркеры, если они не запустились автоматически"
    echo "  - Проверьте, что все миграции применены"
    echo "  - Проверьте, что фронтенд-ассеты обновились"
    echo ""
}

# ============ ГЛАВНАЯ ФУНКЦИЯ ============

main() {
    log "INFO" "=== НАЧАЛО ОБНОВЛЕНИЯ ПРОЕКТА ==="
    log "INFO" "Ветка: $BRANCH"
    log "INFO" "Окружение: $APP_ENV"
    log "INFO" "Лог: $LOG_FILE"

    # Создаём директорию для логов
    mkdir -p "$(dirname "$LOG_FILE")"

    # Проверка окружения
    check_environment

    # Создание бэкапа
    backup_database

    # Остановка воркеров перед обновлением
    #stop_workers

    # Обновление кода
    update_code

    # Обновление PHP-зависимостей
    update_php_dependencies

    # Обновление фронтенда
    update_assets

    # Миграции БД
    run_migrations

    # Очистка кэша
    clear_cache

    # Смена владельца кэша
    log "STEP" "Смена владельца каталога var на www-data..."
    sudo chown -R www-data:www-data var
    check_error "chown var"
    log "INFO" "Владелец var изменён ✓"

    # Проверка после обновления
    post_update_check

    # Вывод итогов
    print_summary

    log "INFO" "=== ОБНОВЛЕНИЕ ЗАВЕРШЕНО ==="
}

# Запуск главной функции с обработкой ошибок
trap 'log "ERROR" "Скрипт прерван на строке $LINENO"' ERR
main "$@"
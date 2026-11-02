<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

/**
 * Консольная команда для пакетного переноса данных из базы SQLite в MySQL/MariaDB.
 */
#[AsCommand(
    name: 'app:db:sqlite-to-mysql',
    description: 'Переносит данные из файла базы данных SQLite в текущую базу данных MySQL',
)]
final class MigrateSqliteToMysqlCommand extends Command
{
    /**
     * Таблицы, которые пропускаются по умолчанию.
     */
    private const array EXCLUDED_TABLES = ['doctrine_migration_versions'];

    /**
     * Максимальное число параметров в одном подготовленном запросе MySQL.
     */
    private const int MYSQL_MAX_PLACEHOLDERS = 60000;

    /**
     * Конструктор команды переноса данных.
     *
     * @param Connection $mysqlConnection Активное подключение к целевой базе MySQL
     * @param string     $projectDir      Корневая директория проекта
     */
    public function __construct(
        private readonly Connection $mysqlConnection,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }// end __construct()

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                'sqlite-path',
                's',
                InputOption::VALUE_REQUIRED,
                'Путь к файлу базы данных SQLite (относительно корня проекта или абсолютный)',
                'var/data.db'
            )
            ->addOption(
                'batch-size',
                'b',
                InputOption::VALUE_REQUIRED,
                'Количество записей для вставки за один пакет',
                '500'
            )
            ->addOption(
                'truncate',
                't',
                InputOption::VALUE_NONE,
                'Очистить целевые таблицы MySQL перед переносом данных'
            )
            ->addOption(
                'include-migrations',
                null,
                InputOption::VALUE_NONE,
                'Переносить также таблицу doctrine_migration_versions'
            );
    }// end configure()

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Миграция данных из SQLite в MySQL');

        if (!$this->mysqlConnection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            $io->error('Основное подключение Doctrine (DATABASE_URL) должно быть настроено на MySQL или MariaDB.');

            return Command::FAILURE;
        }

        $sqlitePathOption = (string) $input->getOption('sqlite-path');
        $sqlitePath = str_starts_with($sqlitePathOption, '/')
            ? $sqlitePathOption
            : $this->projectDir . '/' . ltrim($sqlitePathOption, '/');

        if (!is_file($sqlitePath) || !is_readable($sqlitePath)) {
            $io->error(sprintf('Файл базы данных SQLite не найден или недоступен для чтения: %s', $sqlitePath));

            return Command::FAILURE;
        }

        $batchSize = max(1, (int) $input->getOption('batch-size'));
        $truncate = (bool) $input->getOption('truncate');
        $includeMigrations = (bool) $input->getOption('include-migrations');

        $sqliteConnection = DriverManager::getConnection(
            [
                'driver' => 'pdo_sqlite',
                'path'   => $sqlitePath,
            ]
        );

        try {
            $tables = $this->resolveTargetTables($sqliteConnection, $includeMigrations);

            if ($tables === []) {
                $io->warning('Не найдено общих таблиц между SQLite и MySQL. Убедитесь, что схема создана в MySQL.');

                return Command::SUCCESS;
            }

            $io->text(sprintf('Найдено таблиц для переноса: %d', count($tables)));
            $this->mysqlConnection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

            $summary = [];

            foreach ($tables as $tableName) {
                $migratedRows = $this->migrateTable(
                    $io,
                    $sqliteConnection,
                    $tableName,
                    $batchSize,
                    $truncate
                );
                $summary[] = [
                    $tableName,
                    $migratedRows,
                ];
            }

            $io->newLine();
            $io->table(['Таблица', 'Перенесено записей'], $summary);
            $io->success('Перенос данных из SQLite в MySQL успешно завершен.');

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $io->error(sprintf('Ошибка при переносе данных: %s', $exception->getMessage()));

            return Command::FAILURE;
        } finally {
            try {
                $this->mysqlConnection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
            } catch (Throwable) {
                // Игнорируем ошибку восстановления флага при разрыве соединения.
            }

            $sqliteConnection->close();
        }// end try
    }// end execute()

    /**
     * Определяет список общих таблиц между источником SQLite и целевой базой MySQL.
     *
     * @param Connection $sqliteConnection  Подключение к базе SQLite
     * @param bool       $includeMigrations Включать ли таблицу миграций Doctrine
     *
     * @return list<string>
     *
     * @throws DbalException
     */
    private function resolveTargetTables(Connection $sqliteConnection, bool $includeMigrations): array
    {
        /**
         * Список всех существующих таблиц.
         *
         * @var list<string> $sqliteTables
         */
        $sqliteTables = $sqliteConnection->fetchFirstColumn(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
        );

        /**
         * Список все существующих таблиц.
         *
         * @var list<string> $mysqlTables
         */
        $mysqlTables = $this->mysqlConnection->fetchFirstColumn('SHOW TABLES');

        $commonTables = array_values(array_intersect($mysqlTables, $sqliteTables));

        if (!$includeMigrations) {
            $commonTables = array_values(array_diff($commonTables, self::EXCLUDED_TABLES));
        }

        return $commonTables;
    }// end resolveTargetTables()

    /**
     * Переносит данные одной таблицы из SQLite в MySQL.
     *
     * @param SymfonyStyle $io               Объект ввода-вывода консоли
     * @param Connection   $sqliteConnection Подключение к базе SQLite
     * @param string       $tableName        Имя таблицы
     * @param int          $batchSize        Размер пакета вставки
     * @param bool         $truncate         Флаг предварительной очистки таблицы
     *
     * @return int Количество перенесенных строк
     *
     * @throws Throwable
     */
    private function migrateTable(
        SymfonyStyle $io,
        Connection $sqliteConnection,
        string $tableName,
        int $batchSize,
        bool $truncate,
    ): int {
        $columns = $this->resolveCommonColumns($sqliteConnection, $tableName);

        if ($columns === []) {
            $io->note(sprintf('Таблица "%s" пропущена: нет совпадающих колонок.', $tableName));

            return 0;
        }

        $quotedMysqlTable = $this->quoteMysqlIdentifier($tableName);
        $quotedSqliteTable = $this->quoteSqliteIdentifier($tableName);

        if ($truncate) {
            $this->mysqlConnection->executeStatement(sprintf('TRUNCATE TABLE %s', $quotedMysqlTable));
        }

        $totalRows = (int) $sqliteConnection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $quotedSqliteTable));

        if ($totalRows === 0) {
            return 0;
        }

        $io->section(sprintf('Таблица: %s (записей: %d)', $tableName, $totalRows));
        $progressBar = $io->createProgressBar($totalRows);
        $progressBar->start();

        $effectiveBatchSize = max(
            1,
            min($batchSize, intdiv(self::MYSQL_MAX_PLACEHOLDERS, count($columns)))
        );

        $quotedSqliteColumns = implode(', ', array_map($this->quoteSqliteIdentifier(...), $columns));
        $offset = 0;
        $migratedCount = 0;

        while ($offset < $totalRows) {
            $sql = sprintf(
                'SELECT %s FROM %s LIMIT %d OFFSET %d',
                $quotedSqliteColumns,
                $quotedSqliteTable,
                $effectiveBatchSize,
                $offset
            );

            $rows = $sqliteConnection->fetchAllAssociative($sql);

            if ($rows === []) {
                break;
            }

            $this->mysqlConnection->beginTransaction();

            try {
                $this->insertBatch($tableName, $columns, $rows);
                $this->mysqlConnection->commit();
            } catch (Throwable $exception) {
                $this->mysqlConnection->rollBack();

                throw $exception;
            }

            $rowCount = count($rows);
            $migratedCount += $rowCount;
            $offset += $rowCount;
            $progressBar->advance($rowCount);
        }// end while

        $progressBar->finish();
        $io->newLine(2);

        return $migratedCount;
    }// end migrateTable()

    /**
     * Выполняет множественную вставку (multi-row INSERT) пакета записей в таблицу MySQL.
     *
     * @param string                     $tableName Имя целевой таблицы
     * @param string[]                   $columns   Список колонок для вставки
     * @param array<array<string,mixed>> $rows      Массив строк данных из SQLite
     *
     * @throws DbalException
     */
    private function insertBatch(string $tableName, array $columns, array $rows): void
    {
        $quotedTable = $this->quoteMysqlIdentifier($tableName);
        $quotedColumns = implode(', ', array_map($this->quoteMysqlIdentifier(...), $columns));

        $rowPlaceholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($rows), $rowPlaceholders));

        $params = [];

        foreach ($rows as $row) {
            foreach ($columns as $column) {
                $params[] = $row[$column] ?? null;
            }
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $quotedTable,
            $quotedColumns,
            $allPlaceholders
        );

        $this->mysqlConnection->executeStatement($sql, $params);
    }// end insertBatch()

    /**
     * Возвращает список совпадающих колонок таблицы между SQLite и MySQL.
     *
     * @param Connection $sqliteConnection Подключение к базе SQLite
     * @param string     $tableName        Имя таблицы
     *
     * @return list<string>
     *
     * @throws DbalException
     */
    private function resolveCommonColumns(Connection $sqliteConnection, string $tableName): array
    {
        $sqlitePragmaRows = $sqliteConnection->fetchAllAssociative(
            sprintf('PRAGMA table_info(%s)', $this->quoteSqliteIdentifier($tableName))
        );

        /**
         * Спискок всех столбцов.
         *
         * @var list<string> $sqliteColumns
         */
        $sqliteColumns = array_map(
            static fn (array $col): string => (string) $col['name'],
            $sqlitePragmaRows
        );

        /**
         * Спискок всех столбцов.
         *
         * @var list<string> $mysqlColumns
         */
        $mysqlColumns = $this->mysqlConnection->fetchFirstColumn(
            sprintf('SHOW COLUMNS FROM %s', $this->quoteMysqlIdentifier($tableName))
        );

        return array_values(array_intersect($mysqlColumns, $sqliteColumns));
    }// end resolveCommonColumns()

    /**
     * Экранирует идентификатор для MySQL.
     *
     * @param string $identifier Имя таблицы или колонки
     *
     * @throws DbalException
     */
    private function quoteMysqlIdentifier(string $identifier): string
    {
        return $this->mysqlConnection->getDatabasePlatform()->quoteSingleIdentifier($identifier);
    }// end quoteMysqlIdentifier()

    /**
     * Экранирует идентификатор для SQLite.
     *
     * @param string $identifier Имя таблицы или колонки
     */
    private function quoteSqliteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }// end quoteSqliteIdentifier()
}// end class

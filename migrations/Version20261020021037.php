<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261020021037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update user';
    }// end getDescription()

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof MySQLPlatform) {
            $this->addSql(
                'ALTER TABLE user
                    ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                    ADD updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\''
            );
        }

        if ($platform instanceof SqlitePlatform) {
            $this->addSql('ALTER TABLE user ADD COLUMN created_at DATETIME NULL');
            $this->addSql('ALTER TABLE user ADD COLUMN updated_at DATETIME NULL');
        }
    }// end up()

    /**
     * @inheritDoc
     */
    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof MySQLPlatform) {
            $this->addSql('ALTER TABLE user DROP created_at, DROP updated_at');
        }

        if ($platform instanceof SqlitePlatform) {
            $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, username, roles, password FROM user');
            $this->addSql('DROP TABLE user');
            $this->addSql(
                'CREATE TABLE user (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    username VARCHAR(180) NOT NULL,
                    roles CLOB NOT NULL --(DC2Type:json)
                    , password VARCHAR(255) NOT NULL)'
            );
            $this->addSql(
                'INSERT INTO user (id, username, roles, password)
                SELECT id, username, roles, password FROM __temp__user'
            );
            $this->addSql('DROP TABLE __temp__user');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME ON user (username)');
        }
    }// end down()

    /**
     * Отключает автоматическое оборачивание миграции в транзакцию.
     * Это решает проблему с неявными коммитами (implicit commits) в MySQL/Oracle.
     */
    public function isTransactional(): bool
    {
        $platform = $this->connection->getDatabasePlatform();
        return ($platform instanceof SqlitePlatform);
    }// end isTransactional()
}// end class

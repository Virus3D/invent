<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417024618 extends AbstractMigration
{
    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return '';
    }// end getDescription()

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE user (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                username VARCHAR(180) NOT NULL,
                roles CLOB NOT NULL --(DC2Type:json)
                , password VARCHAR(255) NOT NULL)'
        );
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME ON user (username)');
        $this->addSql(
            'CREATE TABLE rememberme_token (
                series VARCHAR(88) NOT NULL,
                value VARCHAR(88) NOT NULL,
                lastUsed DATETIME NOT NULL --(DC2Type:datetime_immutable)
                , class VARCHAR(100) DEFAULT \'\' NOT NULL,
                username VARCHAR(200) NOT NULL,
                PRIMARY KEY(series))'
        );
        $this->addSql(
            'INSERT INTO user (username, roles, password) VALUES
            (\'pio\', \'["ROLE_USER"]\', \'$2y$13$rmrOmzQq6WCnovxv33S11eOR44h1QLj7l40cpIYNbrHxz4CM92sku\');'
        );
    }// end up()php bin/console doctrine:migrations:execute 'DoctrineMigrations\Version20260417024618' --up

    /**
     * @inheritDoc
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE rememberme_token');
    }// end down()
}// end class

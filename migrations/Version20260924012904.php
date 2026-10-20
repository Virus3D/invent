<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260924012904 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }// end getDescription()

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        if (!$platform instanceof SqlitePlatform) {
            return;
        }

        $this->addSql(
            'CREATE TABLE furniture (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                location_id INTEGER DEFAULT NULL,
                name VARCHAR(200) NOT NULL,
                description CLOB DEFAULT NULL,
                category VARCHAR(20) NOT NULL,
                status VARCHAR(20) DEFAULT NULL,
                purchase_price NUMERIC(15, 2) DEFAULT NULL,
                purchase_date DATE DEFAULT NULL,
                responsible_person VARCHAR(255) DEFAULT NULL,
                checked BOOLEAN DEFAULT 0 NOT NULL,
                specifications CLOB DEFAULT NULL --(DC2Type:json)
                , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
                , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
                , CONSTRAINT FK_665DDAB364D218E FOREIGN KEY (location_id)
                    REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql('CREATE INDEX IDX_665DDAB364D218E ON furniture (location_id)');
    }// end up()

    /**
     * @inheritDoc
     */
    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        if (!$platform instanceof SqlitePlatform) {
            return;
        }

        $this->addSql('DROP TABLE furniture');
    }// end down()
}// end class

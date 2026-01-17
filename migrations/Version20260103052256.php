<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260103052256 extends AbstractMigration
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
            'CREATE TEMPORARY TABLE __temp__inventory_item AS
                SELECT id, location_id, name, description, inventory_number, serial_number,
                        category, specifications, created_at, updated_at FROM inventory_item'
        );
        $this->addSql('DROP TABLE inventory_item');
        $this->addSql(
            'CREATE TABLE inventory_item (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                location_id INTEGER DEFAULT NULL,
                name VARCHAR(200) NOT NULL,
                description CLOB DEFAULT NULL,
                inventory_number VARCHAR(50) NOT NULL,
                serial_number VARCHAR(100) DEFAULT NULL,
                category VARCHAR(20) NOT NULL,
                specifications CLOB DEFAULT NULL, --(DC2Type:json)
                created_at DATETIME NOT NULL, --(DC2Type:datetime_immutable)
                updated_at DATETIME NOT NULL, --(DC2Type:datetime_immutable)
                CONSTRAINT FK_55BDEA3064D218E FOREIGN KEY (location_id)
                    REFERENCES location (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql(
            'INSERT INTO inventory_item (
                id, location_id, name, description, inventory_number, serial_number,
                category, specifications, created_at, updated_at)
            SELECT id, location_id, name, description, inventory_number, serial_number,
                category, specifications, created_at, updated_at FROM __temp__inventory_item'
        );
        $this->addSql('DROP TABLE __temp__inventory_item');
        $this->addSql('CREATE INDEX IDX_55BDEA3064D218E ON inventory_item (location_id)');
    }// end up()

    /**
     * @inheritDoc
     */
    public function down(Schema $schema): void
    {
        $this->addSql(
            'CREATE TEMPORARY TABLE __temp__inventory_item AS
                SELECT id, location_id, name, description, inventory_number, serial_number,
                    category, specifications, created_at, updated_at FROM inventory_item'
        );
        $this->addSql('DROP TABLE inventory_item');
        $this->addSql(
            'CREATE TABLE inventory_item (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                location_id INTEGER DEFAULT NULL,
                name VARCHAR(200) NOT NULL,
                description CLOB DEFAULT NULL,
                inventory_number VARCHAR(50) NOT NULL,
                serial_number VARCHAR(100) DEFAULT NULL,
                category VARCHAR(20) NOT NULL,
                specifications CLOB DEFAULT NULL, --(DC2Type:json)
                created_at DATETIME NOT NULL, --(DC2Type:datetime_immutable)
                updated_at DATETIME NOT NULL, --(DC2Type:datetime_immutable)
                CONSTRAINT FK_55BDEA3064D218E FOREIGN KEY (location_id)
                    REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql(
            'INSERT INTO inventory_item (
                id, location_id, name, description, inventory_number, serial_number,
                category, specifications, created_at, updated_at)
            SELECT id, location_id, name, description, inventory_number, serial_number,
                category, specifications, created_at, updated_at FROM __temp__inventory_item'
        );
        $this->addSql('DROP TABLE __temp__inventory_item');
        $this->addSql('CREATE INDEX IDX_55BDEA3064D218E ON inventory_item (location_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_55BDEA30964C83FF ON inventory_item (inventory_number)');
    }// end down()
}// end class

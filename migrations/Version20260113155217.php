<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260113155217 extends AbstractMigration
{
    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return '';
    }// end getDescription()

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE balance_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                inventory_item_id INTEGER NOT NULL,
                previous_balance_type VARCHAR(20) NOT NULL,
                new_balance_type VARCHAR(20) NOT NULL,
                reason CLOB DEFAULT NULL,
                changed_by VARCHAR(255) DEFAULT NULL,
                changed_at DATETIME NOT NULL, --(DC2Type:datetime_immutable)
                additional_data CLOB DEFAULT NULL, --(DC2Type:json)
            CONSTRAINT FK_135152F1536BF4A2 FOREIGN KEY (inventory_item_id)
                REFERENCES inventory_item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql('CREATE INDEX IDX_135152F1536BF4A2 ON balance_history (inventory_item_id)');
        $this->addSql(
            'CREATE TEMPORARY TABLE __temp__inventory_item AS SELECT id, location_id, name, description,
                inventory_number, serial_number, category, specifications, created_at, updated_at FROM inventory_item'
        );
        $this->addSql('DROP TABLE inventory_item');
        $this->addSql(
            'CREATE TABLE inventory_item (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                location_id INTEGER DEFAULT NULL,
                name VARCHAR(200) NOT NULL,
                description CLOB DEFAULT NULL,
                inventory_number VARCHAR(50) DEFAULT NULL,
                serial_number VARCHAR(100) DEFAULT NULL,
                category VARCHAR(20) NOT NULL,
                specifications CLOB DEFAULT NULL, --(DC2Type:json)
                created_at DATETIME NOT NULL, --(DC2Type:datetime_immutable)
                updated_at DATETIME NOT NULL, --(DC2Type:datetime_immutable)
                balance_type VARCHAR(20) NOT NULL,
                status VARCHAR(20) NOT NULL,
                type VARCHAR(20) NOT NULL,
                purchase_price NUMERIC(15, 2) DEFAULT NULL,
                purchase_date DATE DEFAULT NULL,
                commissioning_date DATE DEFAULT NULL,
                useful_life_months INTEGER DEFAULT NULL,
                responsible_person VARCHAR(255) DEFAULT NULL,
            CONSTRAINT FK_55BDEA3064D218E FOREIGN KEY (location_id)
                REFERENCES location (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql(
            'INSERT INTO inventory_item (id, location_id, name, description, inventory_number,
                serial_number, category, specifications, created_at, updated_at, balance_type, status, type)
            SELECT id, location_id, name, description, inventory_number, serial_number,
                category, specifications, created_at, updated_at, \'on_balance\', \'new\', \'fixed_asset\'
            FROM __temp__inventory_item'
        );
        $this->addSql('DROP TABLE __temp__inventory_item');
        $this->addSql('CREATE INDEX IDX_55BDEA3064D218E ON inventory_item (location_id)');
        $this->addSql(
            'CREATE TEMPORARY TABLE __temp__messenger_messages AS
                SELECT id, body, headers, queue_name, created_at, available_at, delivered_at FROM messenger_messages'
        );
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql(
            'CREATE TABLE messenger_messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                body CLOB NOT NULL,
                headers CLOB NOT NULL,
                queue_name VARCHAR(190) NOT NULL,
                created_at DATETIME NOT NULL, --(DC2Type:datetime_immutable)
                available_at DATETIME NOT NULL, --(DC2Type:datetime_immutable)
                delivered_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )'
        );
        $this->addSql(
            'INSERT INTO messenger_messages (id, body, headers, queue_name, created_at, available_at, delivered_at)
                SELECT id, body, headers, queue_name, created_at, available_at, delivered_at
                FROM __temp__messenger_messages'
        );
        $this->addSql('DROP TABLE __temp__messenger_messages');
        $this->addSql(
            'CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750
            ON messenger_messages (queue_name, available_at, delivered_at, id)'
        );
    }// end up()

    /**
     * {@inheritDoc}
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE balance_history');
        $this->addSql(
            'CREATE TEMPORARY TABLE __temp__inventory_item AS
                SELECT id, location_id, name, description, inventory_number, serial_number, category,
                    specifications, created_at, updated_at FROM inventory_item'
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
            'INSERT INTO inventory_item (id, location_id, name, description, inventory_number,
                serial_number, category, specifications, created_at, updated_at)
            SELECT id, location_id, name, description, inventory_number, serial_number, category,
                specifications, created_at, updated_at FROM __temp__inventory_item'
        );
        $this->addSql('DROP TABLE __temp__inventory_item');
        $this->addSql('CREATE INDEX IDX_55BDEA3064D218E ON inventory_item (location_id)');
        $this->addSql(
            'CREATE TEMPORARY TABLE __temp__messenger_messages AS
                SELECT id, body, headers, queue_name, created_at, available_at, delivered_at FROM messenger_messages'
        );
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql(
            'CREATE TABLE messenger_messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                body CLOB NOT NULL,
                headers CLOB NOT NULL,
                queue_name VARCHAR(190) NOT NULL,
                created_at DATETIME NOT NULL, --(DC2Type:datetime_immutable)
                available_at DATETIME NOT NULL, --(DC2Type:datetime_immutable)
                delivered_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )'
        );
        $this->addSql(
            'INSERT INTO messenger_messages (id, body, headers, queue_name, created_at, available_at, delivered_at)
            SELECT id, body, headers, queue_name, created_at, available_at, delivered_at FROM __temp__messenger_messages'
        );
        $this->addSql('DROP TABLE __temp__messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
    }// end down()
}// end class

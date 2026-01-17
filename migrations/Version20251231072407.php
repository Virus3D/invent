<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251231072407 extends AbstractMigration
{
    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'Create inventory system tables';
    }// end getDescription()

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE inventory_item (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                location_id INTEGER DEFAULT NULL,
                name VARCHAR(200) NOT NULL,
                description CLOB DEFAULT NULL,
                inventory_number VARCHAR(50) NOT NULL,
                serial_number VARCHAR(100) DEFAULT NULL,
                category VARCHAR(50) NOT NULL,
                specifications CLOB DEFAULT NULL, --(DC2Type:json)
                created_at DATETIME NOT NULL, --(DC2Type:datetime_immutable)
                updated_at DATETIME NOT NULL, --(DC2Type:datetime_immutable)
                CONSTRAINT FK_55BDEA3064D218E FOREIGN KEY (location_id)
                    REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql('CREATE UNIQUE INDEX UNIQ_55BDEA30964C83FF ON inventory_item (inventory_number)');
        $this->addSql('CREATE INDEX IDX_55BDEA3064D218E ON inventory_item (location_id)');
        $this->addSql(
            'CREATE TABLE location (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                name VARCHAR(100) NOT NULL,
                room_number VARCHAR(10) NOT NULL,
                description VARCHAR(255) DEFAULT NULL)'
        );
        $this->addSql(
            'CREATE TABLE movement_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                inventory_item_id INTEGER NOT NULL,
                from_location_id INTEGER DEFAULT NULL,
                to_location_id INTEGER DEFAULT NULL,
                moved_at DATETIME NOT NULL, --(DC2Type:datetime_immutable)
                reason VARCHAR(255) DEFAULT NULL, moved_by VARCHAR(100) NOT NULL,
                CONSTRAINT FK_AC7BA86D536BF4A2 FOREIGN KEY (inventory_item_id)
                    REFERENCES inventory_item (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
                CONSTRAINT FK_AC7BA86D980210EB FOREIGN KEY (from_location_id)
                    REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
                CONSTRAINT FK_AC7BA86D28DE1FED FOREIGN KEY (to_location_id)
                    REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql('CREATE INDEX IDX_AC7BA86D536BF4A2 ON movement_log (inventory_item_id)');
        $this->addSql('CREATE INDEX IDX_AC7BA86D980210EB ON movement_log (from_location_id)');
        $this->addSql('CREATE INDEX IDX_AC7BA86D28DE1FED ON movement_log (to_location_id)');
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
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
    }// end up()

    /**
     * @inheritDoc
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE inventory_item');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE movement_log');
        $this->addSql('DROP TABLE messenger_messages');
    }// end down()
}// end class

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260127041753 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add material';
    }// end getDescription()

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE material (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, location_id INTEGER DEFAULT NULL, name VARCHAR(200) NOT NULL, description CLOB DEFAULT NULL, quantity NUMERIC(10, 2) DEFAULT \'0\' NOT NULL, checked BOOLEAN DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_7CBE759564D218E FOREIGN KEY (location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql('CREATE INDEX IDX_7CBE759564D218E ON material (location_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__inventory_item AS SELECT id, location_id, name, description, inventory_number, serial_number, category, specifications, created_at, updated_at, balance_type, status, type, purchase_price, purchase_date, commissioning_date, responsible_person, checked FROM inventory_item');
        $this->addSql('DROP TABLE inventory_item');
        $this->addSql(
            'CREATE TABLE inventory_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, location_id INTEGER DEFAULT NULL, name VARCHAR(200) NOT NULL, description CLOB DEFAULT NULL, inventory_number VARCHAR(50) DEFAULT NULL, serial_number VARCHAR(100) DEFAULT NULL, category VARCHAR(20) NOT NULL, specifications CLOB DEFAULT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , balance_type VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, type VARCHAR(20) NOT NULL, purchase_price NUMERIC(15, 2) DEFAULT NULL, purchase_date DATE DEFAULT NULL, commissioning_date DATE DEFAULT NULL, responsible_person VARCHAR(255) DEFAULT NULL, checked BOOLEAN DEFAULT 0 NOT NULL, CONSTRAINT FK_55BDEA3064D218E FOREIGN KEY (location_id) REFERENCES location (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql('INSERT INTO inventory_item (id, location_id, name, description, inventory_number, serial_number, category, specifications, created_at, updated_at, balance_type, status, type, purchase_price, purchase_date, commissioning_date, responsible_person, checked) SELECT id, location_id, name, description, inventory_number, serial_number, category, specifications, created_at, updated_at, balance_type, status, type, purchase_price, purchase_date, commissioning_date, responsible_person, checked FROM __temp__inventory_item');
        $this->addSql('DROP TABLE __temp__inventory_item');
        $this->addSql('CREATE INDEX IDX_55BDEA3064D218E ON inventory_item (location_id)');
    }// end up()

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE material');
        $this->addSql('ALTER TABLE inventory_item ADD COLUMN useful_life_months INTEGER DEFAULT NULL');
    }// end down()
}// end class

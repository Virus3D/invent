<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260924153744 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__furniture AS SELECT id, location_id, name, description, category, status, purchase_price, purchase_date, responsible_person, checked, created_at, updated_at FROM furniture');
        $this->addSql('DROP TABLE furniture');
        $this->addSql('CREATE TABLE furniture (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, location_id INTEGER DEFAULT NULL, name VARCHAR(200) NOT NULL, description CLOB DEFAULT NULL, category VARCHAR(20) NOT NULL, status VARCHAR(20) DEFAULT NULL, purchase_price NUMERIC(15, 2) DEFAULT NULL, purchase_date DATE DEFAULT NULL, responsible_person VARCHAR(255) DEFAULT NULL, checked BOOLEAN DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , inventory_number VARCHAR(50) DEFAULT NULL, balance_type VARCHAR(20) NOT NULL, CONSTRAINT FK_665DDAB364D218E FOREIGN KEY (location_id) REFERENCES location (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO furniture (id, location_id, name, description, category, status, purchase_price, purchase_date, responsible_person, checked, created_at, updated_at) SELECT id, location_id, name, description, category, status, purchase_price, purchase_date, responsible_person, checked, created_at, updated_at FROM __temp__furniture');
        $this->addSql('DROP TABLE __temp__furniture');
        $this->addSql('CREATE INDEX IDX_665DDAB364D218E ON furniture (location_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__furniture AS SELECT id, location_id, name, description, category, status, purchase_price, purchase_date, responsible_person, checked, created_at, updated_at FROM furniture');
        $this->addSql('DROP TABLE furniture');
        $this->addSql('CREATE TABLE furniture (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, location_id INTEGER DEFAULT NULL, name VARCHAR(200) NOT NULL, description CLOB DEFAULT NULL, category VARCHAR(20) NOT NULL, status VARCHAR(20) DEFAULT NULL, purchase_price NUMERIC(15, 2) DEFAULT NULL, purchase_date DATE DEFAULT NULL, responsible_person VARCHAR(255) DEFAULT NULL, checked BOOLEAN DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , specifications CLOB DEFAULT NULL --(DC2Type:json)
        , CONSTRAINT FK_665DDAB364D218E FOREIGN KEY (location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO furniture (id, location_id, name, description, category, status, purchase_price, purchase_date, responsible_person, checked, created_at, updated_at) SELECT id, location_id, name, description, category, status, purchase_price, purchase_date, responsible_person, checked, created_at, updated_at FROM __temp__furniture');
        $this->addSql('DROP TABLE __temp__furniture');
        $this->addSql('CREATE INDEX IDX_665DDAB364D218E ON furniture (location_id)');
    }
}

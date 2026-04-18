<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260418040327 extends AbstractMigration
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
            'CREATE TABLE material_consumption (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                material_id INTEGER NOT NULL,
                target_location_id INTEGER DEFAULT NULL,
                target_inventory_item_id INTEGER DEFAULT NULL,
                consumed_by_id INTEGER DEFAULT NULL,
                quantity INTEGER NOT NULL,
                target_type VARCHAR(20) NOT NULL,
                consumed_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
                , comment CLOB DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
                , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
                , CONSTRAINT FK_8FE31FF1E308AC6F FOREIGN KEY (material_id)
                    REFERENCES material (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
                CONSTRAINT FK_8FE31FF181776E84 FOREIGN KEY (target_location_id)
                    REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
                CONSTRAINT FK_8FE31FF153BF0D9C FOREIGN KEY (target_inventory_item_id)
                    REFERENCES inventory_item (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
                CONSTRAINT FK_8FE31FF16584D487 FOREIGN KEY (consumed_by_id)
                    REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql('CREATE INDEX IDX_8FE31FF1E308AC6F ON material_consumption (material_id)');
        $this->addSql('CREATE INDEX IDX_8FE31FF181776E84 ON material_consumption (target_location_id)');
        $this->addSql('CREATE INDEX IDX_8FE31FF153BF0D9C ON material_consumption (target_inventory_item_id)');
        $this->addSql('CREATE INDEX IDX_8FE31FF16584D487 ON material_consumption (consumed_by_id)');
        $this->addSql(
            'CREATE TEMPORARY TABLE __temp__material AS SELECT id, location_id, name, description, quantity,
                checked, created_at, updated_at FROM material'
        );
        $this->addSql('DROP TABLE material');
        $this->addSql(
            'CREATE TABLE material (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                location_id INTEGER DEFAULT NULL,
                name VARCHAR(200) NOT NULL,
                description CLOB DEFAULT NULL,
                quantity INTEGER DEFAULT 0 NOT NULL,
                checked BOOLEAN DEFAULT 0 NOT NULL,
                created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
                , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
                , CONSTRAINT FK_7CBE759564D218E FOREIGN KEY (location_id)
                    REFERENCES location (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql(
            'INSERT INTO material (id, location_id, name, description, quantity, checked, created_at, updated_at)
            SELECT id, location_id, name, description, quantity, checked, created_at, updated_at FROM __temp__material'
        );
        $this->addSql('DROP TABLE __temp__material');
        $this->addSql('CREATE INDEX IDX_7CBE759564D218E ON material (location_id)');
        $this->addSql(
            'CREATE TEMPORARY TABLE __temp__software_license AS
                SELECT id, location_id, name, license_key, start_date, end_date, valid, created_at, updated_at
                FROM software_license'
        );
        $this->addSql('DROP TABLE software_license');
        $this->addSql(
            'CREATE TABLE software_license (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                location_id INTEGER DEFAULT NULL,
                name VARCHAR(200) NOT NULL,
                license_key VARCHAR(255) DEFAULT NULL,
                start_date DATE NOT NULL,
                end_date DATE DEFAULT NULL,
                valid BOOLEAN DEFAULT 1 NOT NULL,
                created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
                , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
                , CONSTRAINT FK_SOFTWARE_LICENSE_LOCATION FOREIGN KEY (location_id)
                    REFERENCES location (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql(
            'INSERT INTO software_license (id, location_id, name, license_key, start_date, end_date,
                valid, created_at, updated_at) SELECT id, location_id, name, license_key, start_date,
                end_date, valid, created_at, updated_at FROM __temp__software_license'
        );
        $this->addSql('DROP TABLE __temp__software_license');
        $this->addSql('CREATE INDEX IDX_6133628D64D218E ON software_license (location_id)');
    }// end up()

    /**
     * @inheritDoc
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE material_consumption');
        $this->addSql(
            'CREATE TEMPORARY TABLE __temp__material AS
            SELECT id, location_id, name, description, quantity, checked, created_at, updated_at FROM material'
        );
        $this->addSql('DROP TABLE material');
        $this->addSql(
            'CREATE TABLE material (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                location_id INTEGER DEFAULT NULL,
                name VARCHAR(200) NOT NULL,
                description CLOB DEFAULT NULL,
                quantity NUMERIC(10, 2) DEFAULT \'0\' NOT NULL,
                checked BOOLEAN DEFAULT 0 NOT NULL,
                created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
                , updated_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
                , CONSTRAINT FK_7CBE759564D218E FOREIGN KEY (location_id)
                    REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql(
            'INSERT INTO material (id, location_id, name, description, quantity, checked, created_at, updated_at)
            SELECT id, location_id, name, description, quantity, checked, created_at, updated_at FROM __temp__material'
        );
        $this->addSql('DROP TABLE __temp__material');
        $this->addSql('CREATE INDEX IDX_7CBE759564D218E ON material (location_id)');
        $this->addSql(
            'CREATE TEMPORARY TABLE __temp__software_license AS
            SELECT id, location_id, name, license_key, start_date, end_date, valid, created_at, updated_at
            FROM software_license'
        );
        $this->addSql('DROP TABLE software_license');
        $this->addSql(
            'CREATE TABLE software_license (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                location_id INTEGER DEFAULT NULL,
                name VARCHAR(200) NOT NULL,
                license_key VARCHAR(255) DEFAULT NULL,
                start_date DATE NOT NULL,
                end_date DATE DEFAULT NULL,
                valid BOOLEAN DEFAULT 1 NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                CONSTRAINT FK_6133628D64D218E FOREIGN KEY (location_id)
                    REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE)'
        );
        $this->addSql(
            'INSERT INTO software_license (id, location_id, name, license_key, start_date, end_date,
                valid, created_at, updated_at) SELECT id, location_id, name, license_key, start_date, end_date,
                valid, created_at, updated_at FROM __temp__software_license'
        );
        $this->addSql('DROP TABLE __temp__software_license');
        $this->addSql('CREATE INDEX IDX_SOFTWARE_LICENSE_LOCATION ON software_license (location_id)');
    }// end down()
}// end class

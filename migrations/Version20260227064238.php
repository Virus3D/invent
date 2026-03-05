<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260227064238 extends AbstractMigration
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
            'CREATE TABLE cartridge_installations (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                cartridge_id INTEGER NOT NULL,
                printer_id INTEGER NOT NULL,
                installed_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
                , removed_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
                , printed_pages INTEGER DEFAULT NULL,
                comment VARCHAR(255) DEFAULT NULL,
                CONSTRAINT FK_6474507F376494CA FOREIGN KEY (cartridge_id)
                REFERENCES cartridges (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE,
                CONSTRAINT FK_6474507F46EC494A FOREIGN KEY (printer_id)
                REFERENCES inventory_item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
            )'
        );
        $this->addSql('CREATE INDEX IDX_6474507F376494CA ON cartridge_installations (cartridge_id)');
        $this->addSql('CREATE INDEX IDX_6474507F46EC494A ON cartridge_installations (printer_id)');
        $this->addSql('CREATE INDEX IDX_6474507F46EC494A455180A5 ON cartridge_installations (printer_id, removed_at)');
        $this->addSql(
            'CREATE TABLE cartridges (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                color VARCHAR(50) DEFAULT NULL,
                yield_pages INTEGER DEFAULT NULL,
                stock_quantity INTEGER NOT NULL
            )'
        );
        $this->addSql(
            'CREATE TABLE cartridge_printer_compatibility (
                cartridge_id INTEGER NOT NULL,
                inventory_item_id INTEGER NOT NULL,
                PRIMARY KEY(cartridge_id, inventory_item_id),
                CONSTRAINT FK_DE31F6FA376494CA FOREIGN KEY (cartridge_id)
                REFERENCES cartridges (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE,
                CONSTRAINT FK_DE31F6FA536BF4A2 FOREIGN KEY (inventory_item_id)
                REFERENCES inventory_item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
            )'
        );
        $this->addSql('CREATE INDEX IDX_DE31F6FA376494CA ON cartridge_printer_compatibility (cartridge_id)');
        $this->addSql('CREATE INDEX IDX_DE31F6FA536BF4A2 ON cartridge_printer_compatibility (inventory_item_id)');
    }// end up()

    /**
     * @inheritDoc
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE cartridge_installations');
        $this->addSql('DROP TABLE cartridges');
        $this->addSql('DROP TABLE cartridge_printer_compatibility');
    }// end down()
}// end class

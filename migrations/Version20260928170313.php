<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260928170313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__movement_log AS SELECT id, inventory_item_id, from_location_id, to_location_id, moved_at, reason, moved_by FROM movement_log');
        $this->addSql('DROP TABLE movement_log');
        $this->addSql('CREATE TABLE movement_log (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, inventory_item_id INTEGER DEFAULT NULL, from_location_id INTEGER DEFAULT NULL, to_location_id INTEGER DEFAULT NULL, furniture_id INTEGER DEFAULT NULL, moved_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , reason VARCHAR(255) DEFAULT NULL, moved_by VARCHAR(100) NOT NULL, CONSTRAINT FK_AC7BA86D536BF4A2 FOREIGN KEY (inventory_item_id) REFERENCES inventory_item (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_AC7BA86D980210EB FOREIGN KEY (from_location_id) REFERENCES location (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_AC7BA86D28DE1FED FOREIGN KEY (to_location_id) REFERENCES location (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_AC7BA86DCF5485C3 FOREIGN KEY (furniture_id) REFERENCES furniture (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO movement_log (id, inventory_item_id, from_location_id, to_location_id, moved_at, reason, moved_by) SELECT id, inventory_item_id, from_location_id, to_location_id, moved_at, reason, moved_by FROM __temp__movement_log');
        $this->addSql('DROP TABLE __temp__movement_log');
        $this->addSql('CREATE INDEX IDX_AC7BA86D28DE1FED ON movement_log (to_location_id)');
        $this->addSql('CREATE INDEX IDX_AC7BA86D980210EB ON movement_log (from_location_id)');
        $this->addSql('CREATE INDEX IDX_AC7BA86D536BF4A2 ON movement_log (inventory_item_id)');
        $this->addSql('CREATE INDEX IDX_AC7BA86DCF5485C3 ON movement_log (furniture_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__movement_log AS SELECT id, inventory_item_id, from_location_id, to_location_id, moved_at, reason, moved_by FROM movement_log');
        $this->addSql('DROP TABLE movement_log');
        $this->addSql('CREATE TABLE movement_log (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, inventory_item_id INTEGER NOT NULL, from_location_id INTEGER DEFAULT NULL, to_location_id INTEGER DEFAULT NULL, moved_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , reason VARCHAR(255) DEFAULT NULL, moved_by VARCHAR(100) NOT NULL, CONSTRAINT FK_AC7BA86D536BF4A2 FOREIGN KEY (inventory_item_id) REFERENCES inventory_item (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_AC7BA86D980210EB FOREIGN KEY (from_location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_AC7BA86D28DE1FED FOREIGN KEY (to_location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO movement_log (id, inventory_item_id, from_location_id, to_location_id, moved_at, reason, moved_by) SELECT id, inventory_item_id, from_location_id, to_location_id, moved_at, reason, moved_by FROM __temp__movement_log');
        $this->addSql('DROP TABLE __temp__movement_log');
        $this->addSql('CREATE INDEX IDX_AC7BA86D536BF4A2 ON movement_log (inventory_item_id)');
        $this->addSql('CREATE INDEX IDX_AC7BA86D980210EB ON movement_log (from_location_id)');
        $this->addSql('CREATE INDEX IDX_AC7BA86D28DE1FED ON movement_log (to_location_id)');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261020011831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'For MySQL';
    }// end getDescription()

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        if (!$platform instanceof MySQLPlatform) {
            return;
        }

        $this->addSql(
            'CREATE TABLE balance_history (
                id INT AUTO_INCREMENT NOT NULL,
                inventory_item_id INT NOT NULL,
                previous_balance_type VARCHAR(20) NOT NULL,
                new_balance_type VARCHAR(20) NOT NULL,
                reason LONGTEXT DEFAULT NULL,
                changed_by VARCHAR(255) DEFAULT NULL,
                changed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                additional_data JSON DEFAULT NULL,
                INDEX IDX_135152F1536BF4A2 (inventory_item_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE cartridge_installations (
                id INT AUTO_INCREMENT NOT NULL,
                cartridge_id INT NOT NULL,
                printer_id INT NOT NULL,
                installed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                removed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                printed_pages INT DEFAULT NULL,
                comment VARCHAR(255) DEFAULT NULL,
                INDEX IDX_6474507F46EC494A455180A5 (printer_id, removed_at),
                INDEX IDX_6474507F376494CA (cartridge_id),
                INDEX IDX_6474507F46EC494A (printer_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE cartridges (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                color VARCHAR(50) DEFAULT NULL,
                yield_pages INT DEFAULT NULL,
                stock_quantity INT NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE cartridge_printer_compatibility (
                cartridge_id INT NOT NULL,
                inventory_item_id INT NOT NULL,
                INDEX IDX_DE31F6FA376494CA (cartridge_id),
                INDEX IDX_DE31F6FA536BF4A2 (inventory_item_id),
                PRIMARY KEY(cartridge_id, inventory_item_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE furniture (
                id INT AUTO_INCREMENT NOT NULL,
                location_id INT DEFAULT NULL,
                name VARCHAR(200) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                inventory_number VARCHAR(50) DEFAULT NULL,
                category VARCHAR(20) NOT NULL,
                balance_type VARCHAR(20) NOT NULL,
                status VARCHAR(20) DEFAULT NULL,
                purchase_price NUMERIC(15, 2) DEFAULT NULL,
                purchase_date DATE DEFAULT NULL,
                responsible_person VARCHAR(255) DEFAULT NULL,
                checked TINYINT(1) DEFAULT 0 NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX IDX_665DDAB364D218E (location_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE inventory_item (
                id INT AUTO_INCREMENT NOT NULL,
                location_id INT DEFAULT NULL,
                name VARCHAR(200) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                inventory_number VARCHAR(50) DEFAULT NULL,
                serial_number VARCHAR(100) DEFAULT NULL,
                category VARCHAR(20) NOT NULL,
                balance_type VARCHAR(20) NOT NULL,
                status VARCHAR(20) NOT NULL,
                type VARCHAR(20) NOT NULL,
                purchase_price NUMERIC(15, 2) DEFAULT NULL,
                checked TINYINT(1) DEFAULT 0 NOT NULL,
                purchase_date DATE DEFAULT NULL,
                commissioning_date DATE DEFAULT NULL,
                responsible_person VARCHAR(255) DEFAULT NULL,
                specifications JSON DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX IDX_55BDEA3064D218E (location_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE location (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(100) NOT NULL,
                room_number VARCHAR(10) NOT NULL,
                description VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE material (
                id INT AUTO_INCREMENT NOT NULL,
                location_id INT DEFAULT NULL,
                name VARCHAR(200) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                quantity INT DEFAULT 0 NOT NULL,
                checked TINYINT(1) DEFAULT 0 NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX IDX_7CBE759564D218E (location_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE material_consumption (
                id INT AUTO_INCREMENT NOT NULL,
                material_id INT NOT NULL,
                target_location_id INT DEFAULT NULL,
                target_inventory_item_id INT DEFAULT NULL,
                consumed_by_id INT DEFAULT NULL,
                quantity INT NOT NULL,
                target_type VARCHAR(20) NOT NULL,
                consumed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                comment LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX IDX_8FE31FF1E308AC6F (material_id),
                INDEX IDX_8FE31FF181776E84 (target_location_id),
                INDEX IDX_8FE31FF153BF0D9C (target_inventory_item_id),
                INDEX IDX_8FE31FF16584D487 (consumed_by_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE movement_log (
                id INT AUTO_INCREMENT NOT NULL,
                inventory_item_id INT DEFAULT NULL,
                furniture_id INT DEFAULT NULL,
                from_location_id INT DEFAULT NULL,
                to_location_id INT DEFAULT NULL,
                moved_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                reason VARCHAR(255) DEFAULT NULL,
                moved_by VARCHAR(100) NOT NULL,
                INDEX IDX_AC7BA86D536BF4A2 (inventory_item_id),
                INDEX IDX_AC7BA86DCF5485C3 (furniture_id),
                INDEX IDX_AC7BA86D980210EB (from_location_id),
                INDEX IDX_AC7BA86D28DE1FED (to_location_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE software_license (
                id INT AUTO_INCREMENT NOT NULL,
                location_id INT DEFAULT NULL,
                name VARCHAR(200) NOT NULL,
                license_key VARCHAR(255) DEFAULT NULL,
                start_date DATE NOT NULL,
                end_date DATE DEFAULT NULL,
                valid TINYINT(1) DEFAULT 1 NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX IDX_6133628D64D218E (location_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE user (
                id INT AUTO_INCREMENT NOT NULL,
                username VARCHAR(180) NOT NULL,
                roles JSON NOT NULL,
                password VARCHAR(255) NOT NULL,
                UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME (username),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE messenger_messages (
                id BIGINT AUTO_INCREMENT NOT NULL,
                body LONGTEXT NOT NULL,
                headers LONGTEXT NOT NULL,
                queue_name VARCHAR(190) NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE rememberme_token (
                series VARCHAR(88) NOT NULL,
                value VARCHAR(88) NOT NULL,
                lastUsed DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                class VARCHAR(100) DEFAULT \'\' NOT NULL,
                username VARCHAR(200) NOT NULL,
                PRIMARY KEY(series)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE balance_history ADD CONSTRAINT FK_135152F1536BF4A2
            FOREIGN KEY (inventory_item_id) REFERENCES inventory_item (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE cartridge_installations ADD CONSTRAINT FK_6474507F376494CA
            FOREIGN KEY (cartridge_id) REFERENCES cartridges (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE cartridge_installations ADD CONSTRAINT FK_6474507F46EC494A
            FOREIGN KEY (printer_id) REFERENCES inventory_item (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE cartridge_printer_compatibility ADD CONSTRAINT FK_DE31F6FA376494CA
            FOREIGN KEY (cartridge_id) REFERENCES cartridges (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE cartridge_printer_compatibility ADD CONSTRAINT FK_DE31F6FA536BF4A2
            FOREIGN KEY (inventory_item_id) REFERENCES inventory_item (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE furniture ADD CONSTRAINT FK_665DDAB364D218E
            FOREIGN KEY (location_id) REFERENCES location (id)'
        );
        $this->addSql(
            'ALTER TABLE inventory_item ADD CONSTRAINT FK_55BDEA3064D218E
            FOREIGN KEY (location_id) REFERENCES location (id)'
        );
        $this->addSql(
            'ALTER TABLE material ADD CONSTRAINT FK_7CBE759564D218E
            FOREIGN KEY (location_id) REFERENCES location (id)'
        );
        $this->addSql(
            'ALTER TABLE material_consumption ADD CONSTRAINT FK_8FE31FF1E308AC6F
            FOREIGN KEY (material_id) REFERENCES material (id)'
        );
        $this->addSql(
            'ALTER TABLE material_consumption ADD CONSTRAINT FK_8FE31FF181776E84
            FOREIGN KEY (target_location_id) REFERENCES location (id)'
        );
        $this->addSql(
            'ALTER TABLE material_consumption ADD CONSTRAINT FK_8FE31FF153BF0D9C
            FOREIGN KEY (target_inventory_item_id) REFERENCES inventory_item (id)'
        );
        $this->addSql(
            'ALTER TABLE material_consumption ADD CONSTRAINT FK_8FE31FF16584D487
            FOREIGN KEY (consumed_by_id) REFERENCES user (id)'
        );
        $this->addSql(
            'ALTER TABLE movement_log ADD CONSTRAINT FK_AC7BA86D536BF4A2
            FOREIGN KEY (inventory_item_id) REFERENCES inventory_item (id)'
        );
        $this->addSql(
            'ALTER TABLE movement_log ADD CONSTRAINT FK_AC7BA86DCF5485C3
            FOREIGN KEY (furniture_id) REFERENCES furniture (id)'
        );
        $this->addSql(
            'ALTER TABLE movement_log ADD CONSTRAINT FK_AC7BA86D980210EB
            FOREIGN KEY (from_location_id) REFERENCES location (id)'
        );
        $this->addSql(
            'ALTER TABLE movement_log ADD CONSTRAINT FK_AC7BA86D28DE1FED
            FOREIGN KEY (to_location_id) REFERENCES location (id)'
        );
        $this->addSql(
            'ALTER TABLE software_license ADD CONSTRAINT FK_6133628D64D218E
            FOREIGN KEY (location_id) REFERENCES location (id)'
        );
    }// end up()

    /**
     * @inheritDoc
     */
    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();
        if (!$platform instanceof MySQLPlatform) {
            return;
        }

        $this->addSql('ALTER TABLE balance_history DROP FOREIGN KEY FK_135152F1536BF4A2');
        $this->addSql('ALTER TABLE cartridge_installations DROP FOREIGN KEY FK_6474507F376494CA');
        $this->addSql('ALTER TABLE cartridge_installations DROP FOREIGN KEY FK_6474507F46EC494A');
        $this->addSql('ALTER TABLE cartridge_printer_compatibility DROP FOREIGN KEY FK_DE31F6FA376494CA');
        $this->addSql('ALTER TABLE cartridge_printer_compatibility DROP FOREIGN KEY FK_DE31F6FA536BF4A2');
        $this->addSql('ALTER TABLE furniture DROP FOREIGN KEY FK_665DDAB364D218E');
        $this->addSql('ALTER TABLE inventory_item DROP FOREIGN KEY FK_55BDEA3064D218E');
        $this->addSql('ALTER TABLE material DROP FOREIGN KEY FK_7CBE759564D218E');
        $this->addSql('ALTER TABLE material_consumption DROP FOREIGN KEY FK_8FE31FF1E308AC6F');
        $this->addSql('ALTER TABLE material_consumption DROP FOREIGN KEY FK_8FE31FF181776E84');
        $this->addSql('ALTER TABLE material_consumption DROP FOREIGN KEY FK_8FE31FF153BF0D9C');
        $this->addSql('ALTER TABLE material_consumption DROP FOREIGN KEY FK_8FE31FF16584D487');
        $this->addSql('ALTER TABLE movement_log DROP FOREIGN KEY FK_AC7BA86D536BF4A2');
        $this->addSql('ALTER TABLE movement_log DROP FOREIGN KEY FK_AC7BA86DCF5485C3');
        $this->addSql('ALTER TABLE movement_log DROP FOREIGN KEY FK_AC7BA86D980210EB');
        $this->addSql('ALTER TABLE movement_log DROP FOREIGN KEY FK_AC7BA86D28DE1FED');
        $this->addSql('ALTER TABLE software_license DROP FOREIGN KEY FK_6133628D64D218E');
        $this->addSql('DROP TABLE balance_history');
        $this->addSql('DROP TABLE cartridge_installations');
        $this->addSql('DROP TABLE cartridges');
        $this->addSql('DROP TABLE cartridge_printer_compatibility');
        $this->addSql('DROP TABLE furniture');
        $this->addSql('DROP TABLE inventory_item');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE material');
        $this->addSql('DROP TABLE material_consumption');
        $this->addSql('DROP TABLE movement_log');
        $this->addSql('DROP TABLE software_license');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE rememberme_token');
    }// end down()

    /**
     * Отключает автоматическое оборачивание миграции в транзакцию.
     * Это решает проблему с неявными коммитами (implicit commits) в MySQL/Oracle.
     */
    public function isTransactional(): bool
    {
        return false;
    }// end isTransactional()
}// end class

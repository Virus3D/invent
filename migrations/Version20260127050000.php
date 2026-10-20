<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260127050000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create software_license table';
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
            "CREATE TABLE software_license (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            location_id INTEGER DEFAULT NULL,
            name VARCHAR(200) NOT NULL,
            license_key VARCHAR(255) DEFAULT NULL,
            start_date DATE NOT NULL,
            end_date DATE DEFAULT NULL,
            valid BOOLEAN DEFAULT 1 NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            CONSTRAINT FK_SOFTWARE_LICENSE_LOCATION FOREIGN KEY (location_id)
                REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        )"
        );
        $this->addSql('CREATE INDEX IDX_SOFTWARE_LICENSE_LOCATION ON software_license (location_id)');
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

        $this->addSql('DROP TABLE software_license');
    }// end down()
}// end class

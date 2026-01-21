<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260121173900 extends AbstractMigration
{
    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Add checked flag to inventory_item';
    }// end getDescription()

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory_item ADD checked TINYINT(1) DEFAULT 0 NOT NULL');
    }// end up()

    /**
     * {@inheritDoc}
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inventory_item DROP checked');
    }// end down()
}// end class

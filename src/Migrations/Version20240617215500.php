<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240617215500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update ordonnance table with new fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ordonnance ADD original_name VARCHAR(255) DEFAULT NULL, ADD mime_type VARCHAR(100) DEFAULT NULL, ADD size INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ordonnance DROP original_name, DROP mime_type, DROP size');
    }
}

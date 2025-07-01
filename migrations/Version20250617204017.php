<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250617204017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE medical_file ADD consultation_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE medical_file ADD CONSTRAINT FK_DF6C9C3862FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_DF6C9C3862FF6CDF ON medical_file (consultation_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE medical_file DROP FOREIGN KEY FK_DF6C9C3862FF6CDF
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_DF6C9C3862FF6CDF ON medical_file
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE medical_file DROP consultation_id
        SQL);
    }
}

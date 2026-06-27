<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260627133409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_access_log_organization_created ON access_log (organization_id, created_at)');
        $this->addSql('CREATE INDEX idx_access_log_document_created ON access_log (document_id, created_at)');
        $this->addSql('CREATE INDEX idx_access_log_organization_action ON access_log (organization_id, action)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_access_log_organization_created ON access_log');
        $this->addSql('DROP INDEX idx_access_log_document_created ON access_log');
        $this->addSql('DROP INDEX idx_access_log_organization_action ON access_log');
    }
}

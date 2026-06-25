<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260625140955 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add database indexes for secured document and campaign API queries.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_campaign_organization_created ON campaign (organization_id, created_at)');
        $this->addSql('CREATE INDEX idx_campaign_created_by_created ON campaign (created_by_id, created_at)');
        $this->addSql('CREATE INDEX idx_campaign_status_scheduled ON campaign (status, scheduled_at)');
        $this->addSql('CREATE INDEX idx_document_organization_deleted_created ON document (organization_id, is_deleted, created_at)');
        $this->addSql('CREATE INDEX idx_document_owner_deleted_created ON document (owner_id, is_deleted, created_at)');
        $this->addSql('CREATE INDEX idx_document_status ON document (status)');
        $this->addSql('ALTER TABLE `user` RENAME INDEX idx_8d93d64932c8a3de TO idx_user_organization');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_campaign_organization_created ON campaign');
        $this->addSql('DROP INDEX idx_campaign_created_by_created ON campaign');
        $this->addSql('DROP INDEX idx_campaign_status_scheduled ON campaign');
        $this->addSql('DROP INDEX idx_document_organization_deleted_created ON document');
        $this->addSql('DROP INDEX idx_document_owner_deleted_created ON document');
        $this->addSql('DROP INDEX idx_document_status ON document');
        $this->addSql('ALTER TABLE `user` RENAME INDEX idx_user_organization TO IDX_8D93D64932C8A3DE');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260624113153 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE campaign (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(30) NOT NULL, scheduled_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, organization_id INT NOT NULL, created_by_id INT NOT NULL, INDEX IDX_1F1512DD32C8A3DE (organization_id), INDEX IDX_1F1512DDB03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE campaign_document (campaign_id INT NOT NULL, document_id INT NOT NULL, INDEX IDX_EA195C09F639F774 (campaign_id), INDEX IDX_EA195C09C33F7837 (document_id), PRIMARY KEY (campaign_id, document_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE campaign ADD CONSTRAINT FK_1F1512DD32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE campaign ADD CONSTRAINT FK_1F1512DDB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE campaign_document ADD CONSTRAINT FK_EA195C09F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE campaign_document ADD CONSTRAINT FK_EA195C09C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campaign DROP FOREIGN KEY FK_1F1512DD32C8A3DE');
        $this->addSql('ALTER TABLE campaign DROP FOREIGN KEY FK_1F1512DDB03A8386');
        $this->addSql('ALTER TABLE campaign_document DROP FOREIGN KEY FK_EA195C09F639F774');
        $this->addSql('ALTER TABLE campaign_document DROP FOREIGN KEY FK_EA195C09C33F7837');
        $this->addSql('DROP TABLE campaign');
        $this->addSql('DROP TABLE campaign_document');
    }
}

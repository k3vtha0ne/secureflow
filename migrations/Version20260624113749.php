<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260624113749 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE access_log (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(50) NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, document_id INT NOT NULL, organization_id INT NOT NULL, INDEX IDX_EF7F3510A76ED395 (user_id), INDEX IDX_EF7F3510C33F7837 (document_id), INDEX IDX_EF7F351032C8A3DE (organization_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE access_log ADD CONSTRAINT FK_EF7F3510A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE access_log ADD CONSTRAINT FK_EF7F3510C33F7837 FOREIGN KEY (document_id) REFERENCES document (id)');
        $this->addSql('ALTER TABLE access_log ADD CONSTRAINT FK_EF7F351032C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE access_log DROP FOREIGN KEY FK_EF7F3510A76ED395');
        $this->addSql('ALTER TABLE access_log DROP FOREIGN KEY FK_EF7F3510C33F7837');
        $this->addSql('ALTER TABLE access_log DROP FOREIGN KEY FK_EF7F351032C8A3DE');
        $this->addSql('DROP TABLE access_log');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250209022936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task_assignee ADD task_id INT NOT NULL');
        $this->addSql('ALTER TABLE task_assignee ADD CONSTRAINT FK_3C5D16408DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id)');
        $this->addSql('CREATE INDEX IDX_3C5D16408DB60186 ON task_assignee (task_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task_assignee DROP FOREIGN KEY FK_3C5D16408DB60186');
        $this->addSql('DROP INDEX IDX_3C5D16408DB60186 ON task_assignee');
        $this->addSql('ALTER TABLE task_assignee DROP task_id');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240529005117 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE task_history (id INT AUTO_INCREMENT NOT NULL, changed_by_id INT NOT NULL, task_id INT NOT NULL, field VARCHAR(255) NOT NULL, changed_from VARCHAR(255) NOT NULL, changed_to VARCHAR(255) NOT NULL, changed_on DATETIME NOT NULL, INDEX IDX_385B5AA1828AD0A0 (changed_by_id), INDEX IDX_385B5AA18DB60186 (task_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE task_history ADD CONSTRAINT FK_385B5AA1828AD0A0 FOREIGN KEY (changed_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE task_history ADD CONSTRAINT FK_385B5AA18DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task_history DROP FOREIGN KEY FK_385B5AA1828AD0A0');
        $this->addSql('ALTER TABLE task_history DROP FOREIGN KEY FK_385B5AA18DB60186');
        $this->addSql('DROP TABLE task_history');
    }
}

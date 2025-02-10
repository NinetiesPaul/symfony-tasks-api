<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250210024920 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE task_comments (id INT AUTO_INCREMENT NOT NULL, task_id INT NOT NULL, created_by_id INT NOT NULL, comment_text LONGTEXT NOT NULL, created_on DATETIME NOT NULL, INDEX IDX_1F5E7C668DB60186 (task_id), INDEX IDX_1F5E7C66B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE task_comments ADD CONSTRAINT FK_1F5E7C668DB60186 FOREIGN KEY (task_id) REFERENCES tasks (id)');
        $this->addSql('ALTER TABLE task_comments ADD CONSTRAINT FK_1F5E7C66B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task_comments DROP FOREIGN KEY FK_1F5E7C668DB60186');
        $this->addSql('ALTER TABLE task_comments DROP FOREIGN KEY FK_1F5E7C66B03A8386');
        $this->addSql('DROP TABLE task_comments');
    }
}

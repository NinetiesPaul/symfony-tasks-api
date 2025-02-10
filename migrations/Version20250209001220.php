<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250209001220 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE task_assignee (id INT AUTO_INCREMENT NOT NULL, assigned_by_id INT DEFAULT NULL, assigned_to_id INT DEFAULT NULL, task_id_id INT NOT NULL, INDEX IDX_3C5D16406E6F1246 (assigned_by_id), INDEX IDX_3C5D1640F4BD7827 (assigned_to_id), INDEX IDX_3C5D1640B8E08577 (task_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE task_assignee ADD CONSTRAINT FK_3C5D16406E6F1246 FOREIGN KEY (assigned_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE task_assignee ADD CONSTRAINT FK_3C5D1640F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE task_assignee ADD CONSTRAINT FK_3C5D1640B8E08577 FOREIGN KEY (task_id_id) REFERENCES tasks (id)');
        $this->addSql('ALTER TABLE user CHANGE name name VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task_assignee DROP FOREIGN KEY FK_3C5D16406E6F1246');
        $this->addSql('ALTER TABLE task_assignee DROP FOREIGN KEY FK_3C5D1640F4BD7827');
        $this->addSql('ALTER TABLE task_assignee DROP FOREIGN KEY FK_3C5D1640B8E08577');
        $this->addSql('DROP TABLE task_assignee');
        $this->addSql('ALTER TABLE `user` CHANGE name name VARCHAR(255) DEFAULT \'USER\' NOT NULL');
    }
}

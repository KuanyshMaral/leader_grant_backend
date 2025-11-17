<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117131640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD bank_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E911C8FB41 FOREIGN KEY (bank_id) REFERENCES bank (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_1483A5E911C8FB41 ON users (bank_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "users" DROP CONSTRAINT FK_1483A5E911C8FB41');
        $this->addSql('DROP INDEX IDX_1483A5E911C8FB41');
        $this->addSql('ALTER TABLE "users" DROP bank_id');
    }
}

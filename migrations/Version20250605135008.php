<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250605135008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE user_audit_logs DROP CONSTRAINT FK_7148EEB4A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_audit_logs ADD CONSTRAINT FK_7148EEB4A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX uniq_1483a5e95e237e06eb3b4e33 RENAME TO name_deleted_unique
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX uniq_1483a5e9e7927c74eb3b4e33 RENAME TO email_deleted_unique
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_audit_logs DROP CONSTRAINT fk_7148eeb4a76ed395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_audit_logs ADD CONSTRAINT fk_7148eeb4a76ed395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX email_deleted_unique RENAME TO uniq_1483a5e9e7927c74eb3b4e33
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX name_deleted_unique RENAME TO uniq_1483a5e95e237e06eb3b4e33
        SQL);
    }
}

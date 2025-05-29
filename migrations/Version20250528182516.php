<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250528182516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX uniq_1483a5e9e7927c74
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX uniq_1483a5e95e237e06
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_1483A5E95E237E06EB3B4E33 ON users (name, deleted)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74EB3B4E33 ON users (email, deleted)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_1483A5E95E237E06EB3B4E33
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_1483A5E9E7927C74EB3B4E33
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_1483a5e9e7927c74 ON users (email)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_1483a5e95e237e06 ON users (name)
        SQL);
    }
}

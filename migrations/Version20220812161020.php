<?php
declare(strict_types = 1);

// phpcs:ignoreFile
namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220812161020 extends AbstractMigration
{
    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function getDescription(): string
    {
        return 'TODO: Describe reason for this migration';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        $sql = <<<SQL
ALTER TABLE log_request 
    CHANGE headers headers LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
    CHANGE parameters parameters LONGTEXT NOT NULL COMMENT '(DC2Type:json)'
SQL;

        $this->addSql($sql);
    }

    /**
    * @noinspection PhpMissingParentCallCommonInspection
    */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        $sql = <<<SQL
ALTER TABLE log_request 
    CHANGE headers headers LONGTEXT NOT NULL COMMENT '(DC2Type:array)',
    CHANGE parameters parameters LONGTEXT NOT NULL COMMENT '(DC2Type:array)'
SQL;

        $this->addSql($sql);
    }
}

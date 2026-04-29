<?php
declare(strict_types = 1);

// phpcs:ignoreFile
namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

/**
 * Remove legacy (DC2Type:EnumXxx) comment suffixes from ENUM columns.
 *
 * DBAL 3 appended these suffixes so its schema introspector could map
 * DB columns back to custom Doctrine types.  DBAL 4 no longer uses that
 * mechanism; leaving the suffixes causes columnsEqual() to disagree
 * between the ORM-generated schema (no suffix) and the DB schema (with
 * suffix), making SchemaValidator::schemaInSyncWithMetadata() fail.
 */
final class Version20260429000000 extends AbstractMigration
{
    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[Override]
    public function getDescription(): string
    {
        return 'Strip legacy (DC2Type:EnumXxx) comment suffixes from ENUM columns';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql(
            "ALTER TABLE user " .
            "CHANGE language language ENUM('en', 'fi') NOT NULL COMMENT 'User language for translations', " .
            "CHANGE locale locale ENUM('en', 'fi') NOT NULL COMMENT 'User locale for number, time, date, etc. formatting.'"
        );
        $this->addSql(
            "ALTER TABLE log_login CHANGE type type ENUM('failure', 'success') NOT NULL COMMENT ''"
        );
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[Override]
    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql(
            "ALTER TABLE user " .
            "CHANGE language language ENUM('en', 'fi') NOT NULL COMMENT 'User language for translations(DC2Type:EnumLanguage)', " .
            "CHANGE locale locale ENUM('en', 'fi') NOT NULL COMMENT 'User locale for number, time, date, etc. formatting.(DC2Type:EnumLocale)'"
        );
        $this->addSql(
            "ALTER TABLE log_login CHANGE type type ENUM('failure', 'success') NOT NULL COMMENT '(DC2Type:EnumLogLogin)'"
        );
    }
}

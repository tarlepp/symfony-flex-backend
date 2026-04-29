<?php
declare(strict_types = 1);

// phpcs:ignoreFile
namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Override;

/**
 * Strip all legacy (DC2Type:xxx) column comments for DBAL 4 compatibility.
 *
 * DBAL 3 wrote (DC2Type:xxx) hints into column comments so the schema
 * introspector could re-map DB columns back to the custom Doctrine type.
 * DBAL 4 no longer uses that mechanism; instead it reads those comments as
 * plain column comments.  Because the ORM metadata carries no such comments,
 * AbstractPlatform::columnsEqual() (which includes inline comments in the SQL
 * declaration string for MySQL) considers every affected column as "changed",
 * causing SchemaValidator::schemaInSyncWithMetadata() to fail.
 *
 * This migration removes the (DC2Type:xxx)-only comments from every affected
 * column across all tables.  Columns that carry a real comment AND a DC2Type
 * suffix (e.g. user.language / user.locale) were already handled by
 * Version20260429000000.
 */
final class Version20260429100000 extends AbstractMigration
{
    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[Override]
    public function getDescription(): string
    {
        return 'Strip all legacy (DC2Type:xxx) column comments for DBAL 4 compatibility';
    }

    #[Override]
    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql(
            'ALTER TABLE api_key ' .
            'CHANGE id id BINARY(16) NOT NULL, ' .
            'CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL, ' .
            'CHANGE updated_by_id updated_by_id BINARY(16) DEFAULT NULL, ' .
            'CHANGE created_at created_at DATETIME DEFAULT NULL, ' .
            'CHANGE updated_at updated_at DATETIME DEFAULT NULL'
        );

        $this->addSql(
            'ALTER TABLE api_key_has_user_group ' .
            'CHANGE api_key_id api_key_id BINARY(16) NOT NULL, ' .
            'CHANGE user_group_id user_group_id BINARY(16) NOT NULL'
        );

        $this->addSql(
            'ALTER TABLE date_dimension ' .
            'CHANGE id id BINARY(16) NOT NULL, ' .
            'CHANGE `date` `date` DATE NOT NULL'
        );

        $this->addSql(
            'ALTER TABLE healthz ' .
            'CHANGE id id BINARY(16) NOT NULL, ' .
            'CHANGE `timestamp` `timestamp` DATETIME NOT NULL'
        );

        $this->addSql(
            'ALTER TABLE log_login ' .
            'CHANGE id id BINARY(16) NOT NULL, ' .
            'CHANGE user_id user_id BINARY(16) DEFAULT NULL, ' .
            'CHANGE time time DATETIME NOT NULL, ' .
            'CHANGE `date` `date` DATE NOT NULL'
        );

        $this->addSql(
            'ALTER TABLE log_login_failure ' .
            'CHANGE id id BINARY(16) NOT NULL, ' .
            'CHANGE user_id user_id BINARY(16) NOT NULL, ' .
            'CHANGE `timestamp` `timestamp` DATETIME NOT NULL'
        );

        $this->addSql(
            'ALTER TABLE log_request ' .
            'CHANGE id id BINARY(16) NOT NULL, ' .
            'CHANGE user_id user_id BINARY(16) DEFAULT NULL, ' .
            'CHANGE api_key_id api_key_id BINARY(16) DEFAULT NULL, ' .
            'CHANGE time time DATETIME NOT NULL, ' .
            'CHANGE `date` `date` DATE NOT NULL, ' .
            'CHANGE headers headers JSON NOT NULL, ' .
            'CHANGE parameters parameters JSON NOT NULL'
        );

        $this->addSql(
            'ALTER TABLE role ' .
            'CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL, ' .
            'CHANGE updated_by_id updated_by_id BINARY(16) DEFAULT NULL, ' .
            'CHANGE created_at created_at DATETIME DEFAULT NULL, ' .
            'CHANGE updated_at updated_at DATETIME DEFAULT NULL'
        );

        $this->addSql(
            'ALTER TABLE `user` ' .
            'CHANGE id id BINARY(16) NOT NULL, ' .
            'CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL, ' .
            'CHANGE updated_by_id updated_by_id BINARY(16) DEFAULT NULL, ' .
            'CHANGE created_at created_at DATETIME DEFAULT NULL, ' .
            'CHANGE updated_at updated_at DATETIME DEFAULT NULL'
        );

        $this->addSql(
            'ALTER TABLE user_has_user_group ' .
            'CHANGE user_id user_id BINARY(16) NOT NULL, ' .
            'CHANGE user_group_id user_group_id BINARY(16) NOT NULL'
        );

        $this->addSql(
            'ALTER TABLE user_group ' .
            'CHANGE id id BINARY(16) NOT NULL, ' .
            'CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL, ' .
            'CHANGE updated_by_id updated_by_id BINARY(16) DEFAULT NULL, ' .
            'CHANGE created_at created_at DATETIME DEFAULT NULL, ' .
            'CHANGE updated_at updated_at DATETIME DEFAULT NULL'
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
            "ALTER TABLE api_key " .
            "CHANGE id id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE updated_by_id updated_by_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', " .
            "CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'"
        );

        $this->addSql(
            "ALTER TABLE api_key_has_user_group " .
            "CHANGE api_key_id api_key_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE user_group_id user_group_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)'"
        );

        $this->addSql(
            "ALTER TABLE date_dimension " .
            "CHANGE id id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE `date` `date` DATE NOT NULL COMMENT '(DC2Type:date_immutable)'"
        );

        $this->addSql(
            "ALTER TABLE healthz " .
            "CHANGE id id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE `timestamp` `timestamp` DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'"
        );

        $this->addSql(
            "ALTER TABLE log_login " .
            "CHANGE id id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE user_id user_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE time time DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', " .
            "CHANGE `date` `date` DATE NOT NULL COMMENT '(DC2Type:date_immutable)'"
        );

        $this->addSql(
            "ALTER TABLE log_login_failure " .
            "CHANGE id id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE user_id user_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE `timestamp` `timestamp` DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'"
        );

        $this->addSql(
            "ALTER TABLE log_request " .
            "CHANGE id id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE user_id user_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE api_key_id api_key_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE time time DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', " .
            "CHANGE `date` `date` DATE NOT NULL COMMENT '(DC2Type:date_immutable)', " .
            "CHANGE headers headers JSON NOT NULL COMMENT '(DC2Type:json)', " .
            "CHANGE parameters parameters JSON NOT NULL COMMENT '(DC2Type:json)'"
        );

        $this->addSql(
            "ALTER TABLE role " .
            "CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE updated_by_id updated_by_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', " .
            "CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'"
        );

        $this->addSql(
            "ALTER TABLE `user` " .
            "CHANGE id id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE updated_by_id updated_by_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', " .
            "CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'"
        );

        $this->addSql(
            "ALTER TABLE user_has_user_group " .
            "CHANGE user_id user_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE user_group_id user_group_id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)'"
        );

        $this->addSql(
            "ALTER TABLE user_group " .
            "CHANGE id id BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE created_by_id created_by_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE updated_by_id updated_by_id BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid_binary_ordered_time)', " .
            "CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', " .
            "CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'"
        );
    }
}

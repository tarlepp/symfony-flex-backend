<?php

declare(strict_types = 1);

// phpcs:ignoreFile
/** @noinspection PhpIllegalPsrClassPathInspection */

namespace DoctrineMigrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200229154730 extends AbstractMigration
{
    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function getDescription(): string
    {
        return 'User localization support.';
    }

    /**
     * @throws DBALException
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $sql = <<<SQL
ALTER TABLE user 
    ADD language ENUM('en', 'fi') NOT NULL COMMENT 'User language for translations(DC2Type:EnumLanguage)' AFTER email, 
    ADD locale ENUM('en', 'fi') NOT NULL COMMENT 'User locale for number, time, date, etc. formatting.(DC2Type:EnumLocale)' AFTER language, 
    ADD timezone VARCHAR(255) DEFAULT 'Europe/Helsinki' NOT NULL COMMENT 'User timezone which should be used to display time, date, etc.' AFTER locale
SQL;

        $this->addSql($sql);
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws DBALException
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('ALTER TABLE user DROP language, DROP locale, DROP timezone');
    }
}

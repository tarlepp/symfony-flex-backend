<?php
// phpcs:ignoreFile
/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection SqlNoDataSourceInspection */
/** @noinspection SqlDialectInspection */

declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190607143136 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return parent::getDescription() . 'User table column name changes';
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $sql = <<<SQL
ALTER TABLE `user`
    CHANGE COLUMN `firstname` `first_name` VARCHAR(255) NOT NULL,
    CHANGE COLUMN `surname` `last_name` VARCHAR(255) NOT NULL;
SQL;

        $this->addSql($sql);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $sql = <<<SQL
ALTER TABLE `user`
    CHANGE COLUMN `first_name` `firstname` VARCHAR(255) NOT NULL,
    CHANGE COLUMN `last_name` `surname` VARCHAR(255) NOT NULL;
SQL;

        $this->addSql($sql);
    }
}

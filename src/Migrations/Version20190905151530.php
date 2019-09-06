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
final class Version20190905151530 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string
    {
        return parent::getDescription() . 'DateTime to DateTimeImmutable';
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

        $this->addSql('ALTER TABLE healthz CHANGE timestamp timestamp DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user_group CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE log_login CHANGE time time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE date `date` DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE api_key CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE role CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE log_request CHANGE time time DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE date `date` DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE log_login_failure CHANGE timestamp timestamp DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
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

        $this->addSql('ALTER TABLE api_key CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE healthz CHANGE timestamp timestamp DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE log_login CHANGE time time DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', CHANGE `date` date DATE NOT NULL');
        $this->addSql('ALTER TABLE log_login_failure CHANGE timestamp timestamp DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE log_request CHANGE time time DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', CHANGE `date` date DATE NOT NULL');
        $this->addSql('ALTER TABLE role CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE user CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\'');
        $this->addSql('ALTER TABLE user_group CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime)\'');
    }
}

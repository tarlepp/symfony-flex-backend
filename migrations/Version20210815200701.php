<?php
declare(strict_types = 1);

// phpcs:ignoreFile
namespace DoctrineMigrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210815200701 extends AbstractMigration
{
    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function getDescription(): string
    {
        return 'Added some missing comments to column definitions';
    }

    /**
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('ALTER TABLE api_key CHANGE token token VARCHAR(40) NOT NULL COMMENT \'Generated API key string for authentication\'');
        $this->addSql('ALTER TABLE date_dimension CHANGE leap_year leap_year TINYINT(1) NOT NULL COMMENT \'Whether it\'\'s a leap year or not\'');
        $this->addSql('ALTER TABLE user CHANGE password password VARCHAR(255) NOT NULL COMMENT \'Hashed password\'');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Exception
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('ALTER TABLE api_key CHANGE token token VARCHAR(40) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE date_dimension CHANGE leap_year leap_year TINYINT(1) NOT NULL COMMENT \'Whether it\'\'s a leap year\'');
        $this->addSql('ALTER TABLE user CHANGE password password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}

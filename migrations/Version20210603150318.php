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
final class Version20210603150318 extends AbstractMigration
{
    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function getDescription(): string
    {
        return 'Rename `master` => `main`';
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function isTransactional(): bool
    {
        return false;
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

        $this->addSql('ALTER TABLE log_request CHANGE is_master_request is_main_request TINYINT(1) NOT NULL');
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

        $this->addSql('ALTER TABLE log_request CHANGE is_main_request is_master_request TINYINT(1) NOT NULL');
    }
}

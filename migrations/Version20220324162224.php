<?php
declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220324162224 extends AbstractMigration
{
    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function getDescription(): string
    {
        return 'Added username column to log_login table, so that after user deletion there is reference to the user.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE log_login ADD username VARCHAR(255) NOT NULL AFTER type');
    }

    /**
    * @noinspection PhpMissingParentCallCommonInspection
    */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE log_login DROP username');
    }
}

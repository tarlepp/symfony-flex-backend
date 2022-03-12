<?php
declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220312135411 extends AbstractMigration
{
    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function getDescription(): string
    {
        return 'Added missing cascade on delete for the `log_login_failure` table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE log_login_failure DROP FOREIGN KEY FK_EDB4AF3A76ED395');
        $this->addSql('ALTER TABLE log_login_failure ADD CONSTRAINT FK_EDB4AF3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    /**
    * @noinspection PhpMissingParentCallCommonInspection
    */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE log_login_failure DROP FOREIGN KEY FK_EDB4AF3A76ED395');
        $this->addSql('ALTER TABLE log_login_failure ADD CONSTRAINT FK_EDB4AF3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}

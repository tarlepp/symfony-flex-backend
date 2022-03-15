<?php
declare(strict_types = 1);

namespace <namespace>;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class <className> extends AbstractMigration
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
<up>
    }

    /**
    * @noinspection PhpMissingParentCallCommonInspection
    */
    public function down(Schema $schema): void
    {
<down>
    }
}

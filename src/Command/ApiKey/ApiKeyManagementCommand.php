<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/ApiKeyManagementCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\ApiKey;

use App\Command\Traits\ExecuteMultipleCommandTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

/**
 * Class ApiKeyManagementCommand
 *
 * @package App\Command\ApiKey
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[AsCommand(
    name: 'api-key:management',
    description: 'Console command to manage API keys',
)]
class ApiKeyManagementCommand extends Command
{
    use ExecuteMultipleCommandTrait;

    public function __construct()
    {
        parent::__construct();

        $this->setChoices([
            ListApiKeysCommand::NAME => 'List API keys',
            CreateApiKeyCommand::NAME => 'Create API key',
            EditApiKeyCommand::NAME => 'Edit API key',
            ChangeTokenCommand::NAME => 'Change API key token',
            RemoveApiKeyCommand::NAME => 'Remove API key',
            '0' => 'Exit',
        ]);
    }
}

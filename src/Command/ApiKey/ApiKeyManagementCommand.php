<?php
declare(strict_types = 1);

/**
 * /src/Command/ApiKey/ApiKeyManagementCommand.php
 */

namespace App\Command\ApiKey;

use App\Command\Traits\ExecuteMultipleCommandTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

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

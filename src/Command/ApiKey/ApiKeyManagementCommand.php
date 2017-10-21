<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/ApiKeyManagementCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\ApiKey;

use App\Command\Traits\ExecuteMultipleCommandTrait;
use Symfony\Component\Console\Command\Command;

/**
 * Class ApiKeyManagementCommand
 *
 * @package App\Command\ApiKey
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyManagementCommand extends Command
{
    use ExecuteMultipleCommandTrait;

    /**
     * ManagementCommand constructor.
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct()
    {
        parent::__construct('api-key:management');

        $this->setDescription('Console command to manage API keys');

        $this->setChoices([
            'api-key:create'        => 'Create API key',
            'api-key:edit'          => 'Edit API key',
            'api-key:change-token'  => 'Change API key token',
            'api-key:remove'        => 'Remove API key',
            'api-key:list'          => 'List API keys',
            false                   => 'Exit',
        ]);
    }
}

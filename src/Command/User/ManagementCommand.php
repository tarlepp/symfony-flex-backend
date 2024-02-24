<?php
declare(strict_types = 1);
/**
 * /src/Command/User/ManagementCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\User;

use App\Command\Traits\ExecuteMultipleCommandTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;

/**
 * @package App\Command\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[AsCommand(
    name: 'user:management',
    description: 'Console command to manage users and user groups',
)]
class ManagementCommand extends Command
{
    use ExecuteMultipleCommandTrait;

    /**
     * ManagementCommand constructor.
     *
     * @throws LogicException
     */
    public function __construct()
    {
        parent::__construct();

        $this->setChoices([
            ListUsersCommand::NAME => 'List users',
            ListUserGroupsCommand::NAME => 'List user groups',
            CreateUserCommand::NAME => 'Create user',
            CreateUserGroupCommand::NAME => 'Create user group',
            EditUserCommand::NAME => 'Edit user',
            EditUserGroupCommand::NAME => 'Edit user group',
            RemoveUserCommand::NAME => 'Remove user',
            RemoveUserGroupCommand::NAME => 'Remove user group',
            '0' => 'Exit',
        ]);
    }
}

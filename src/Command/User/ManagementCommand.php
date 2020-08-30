<?php
declare(strict_types = 1);
/**
 * /src/Command/User/ManagementCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command\User;

use App\Command\Traits\ExecuteMultipleCommandTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;

/**
 * Class ManagementCommand
 *
 * @package App\Command\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ManagementCommand extends Command
{
    use ExecuteMultipleCommandTrait;

    /**
     * ManagementCommand constructor.
     *
     * @throws LogicException
     *
     * @psalm-suppress InvalidScalarArgument
     */
    public function __construct()
    {
        parent::__construct('user:management');

        $this->setDescription('Console command to manage users and user groups');

        $this->setChoices([
            'user:list' => 'List users',
            'user:list-groups' => 'List user groups',
            'user:create' => 'Create user',
            'user:create-group' => 'Create user group',
            'user:edit' => 'Edit user',
            'user:edit-group' => 'Edit user group',
            'user:remove' => 'Remove user',
            'user:remove-group' => 'Remove user group',
            false => 'Exit',
        ]);
    }
}

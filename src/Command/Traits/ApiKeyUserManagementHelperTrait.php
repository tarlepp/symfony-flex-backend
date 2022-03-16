<?php
declare(strict_types = 1);
/**
 * /src/Command/Traits/ApiKeyUserManagementHelperTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\Traits;

use App\Enum\Role;
use App\Security\RolesService;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Trait ApiKeyUserManagementHelperTrait
 *
 * @package App\Command\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
trait ApiKeyUserManagementHelperTrait
{
    use GetApplicationTrait;

    /**
     * Method to create user groups via existing 'user:create-group' command.
     *
     * @throws Throwable
     */
    protected function createUserGroups(OutputInterface $output): void
    {
        $command = $this->getApplication()->find('user:create-group');

        // Iterate roles and create user group for each one
        foreach (Role::cases() as $role) {
            $arguments = [
                'command' => 'user:create-group',
                '--name' => $role->getLabel(),
                '--role' => $role->value,
                '-n' => true,
            ];

            $input = new ArrayInput($arguments);
            $input->setInteractive(false);

            $command->run($input, $output);
        }
    }
}

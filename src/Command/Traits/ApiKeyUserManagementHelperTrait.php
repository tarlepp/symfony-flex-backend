<?php
declare(strict_types = 1);
/**
 * /src/Command/Traits/ApiKeyUserManagementHelperTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\Traits;

use App\Security\RolesService;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @package App\Command\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
trait ApiKeyUserManagementHelperTrait
{
    use GetApplicationTrait;

    abstract public function getRolesService(): RolesService;

    /**
     * Method to create user groups via existing 'user:create-group' command.
     *
     * @throws Throwable
     */
    protected function createUserGroups(OutputInterface $output): void
    {
        $command = $this->getApplication()->find('user:create-group');

        // Iterate roles and create user group for each one
        foreach ($this->getRolesService()->getRoles() as $role) {
            $arguments = [
                'command' => 'user:create-group',
                '--name' => $this->getRolesService()->getRoleLabel($role),
                '--role' => $role,
                '-n' => true,
            ];

            $input = new ArrayInput($arguments);
            $input->setInteractive(false);

            $command->run($input, $output);
        }
    }
}

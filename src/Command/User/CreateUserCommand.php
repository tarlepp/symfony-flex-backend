<?php
declare(strict_types=1);
/**
 * /src/Command/User/CreateUserCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\User;

use App\Repository\RoleRepository;
use App\Resource\UserGroupResource;
use App\Security\RolesInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CreateUserCommand
 *
 * @package App\Command\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CreateUserCommand extends Command
{
    /**
     * @var UserGroupResource
     */
    private $userGroupResource;

    /**
     * @var RolesInterface
     */
    private $roles;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * CreateRolesCommand constructor.
     *
     * @param null              $name
     * @param UserGroupResource $userGroupResource
     * @param RolesInterface    $roles
     * @param RoleRepository    $roleRepository
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(
        $name = null,
        UserGroupResource $userGroupResource,
        RolesInterface $roles,
        RoleRepository $roleRepository
    )
    {
        parent::__construct('user:create');

        $this->userGroupResource = $userGroupResource;
        $this->roles = $roles;
        $this->roleRepository = $roleRepository;

        $this->setDescription('Console command to create user to database');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        // Check that roles exists
        $this->checkUserGroups($output, $input->isInteractive());

        if ($input->isInteractive()) {
            $this->io->success('User created - have a nice day');
        }

        return null;
    }

    /**
     * Method to check if database contains user groups, if non exists method will run 'user:create-group' command
     * to create those automatically according to '$this->roles->getRoles()' output. Basically this will automatically
     * create user groups for each role that is defined to application.
     *
     * Also note that if groups are not found method will reset application 'role' table content, so that we can be
     * sure that we can create all groups correctly.
     *
     * @param OutputInterface $output
     * @param bool            $interactive
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function checkUserGroups(OutputInterface $output, bool $interactive): void
    {
        if ($this->userGroupResource->count() !== 0) {
            return;
        }

        if ($interactive) {
            $this->io->block([
                'User groups are not yet created, creating those now...'
            ]);
        }

        // Reset roles
        $this->roleRepository->reset();

        $command = $this->getApplication()->find('user:create-group');

        // Iterate roles and create user group for each one
        foreach ($this->roles->getRoles() as $role) {
            $arguments = [
                'command'   => 'user:create-group',
                '--name'    => $this->roles->getRoleLabel($role),
                '--role'    => $role,
                '-n'        => true,
            ];

            $input = new ArrayInput($arguments);
            $input->setInteractive(false);

            $command->run($input, $output);
        }
    }
}

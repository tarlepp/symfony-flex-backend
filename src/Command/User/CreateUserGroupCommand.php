<?php
declare(strict_types = 1);
/**
 * /src/Command/User/CreateUserGroupCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command\User;

use App\Command\HelperConfigure;
use App\Command\Traits\GetApplicationTrait;
use App\Command\Traits\SymfonyStyleTrait;
use App\DTO\UserGroup\UserGroupCreate as UserGroupDto;
use App\Form\Type\Console\UserGroupType;
use App\Repository\RoleRepository;
use App\Resource\UserGroupResource;
use Matthias\SymfonyConsoleForm\Console\Helper\FormHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Class CreateUserGroupCommand
 *
 * @package App\Command\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CreateUserGroupCommand extends Command
{
    use GetApplicationTrait;
    use SymfonyStyleTrait;

    /**
     * @var array<int, array<string, string>>
     */
    private static array $commandParameters = [
        [
            'name' => 'name',
            'description' => 'Name of the user group',
        ],
        [
            'name' => 'role',
            'description' => 'Role of the user group',
        ],
    ];

    private UserGroupResource $userGroupResource;
    private RoleRepository $roleRepository;

    /**
     * CreateUserGroupCommand constructor.
     */
    public function __construct(UserGroupResource $userGroupResource, RoleRepository $roleRepository)
    {
        parent::__construct('user:create-group');

        $this->userGroupResource = $userGroupResource;
        $this->roleRepository = $roleRepository;

        $this->setDescription('Console command to create user groups');
    }

    protected function configure(): void
    {
        parent::configure();

        HelperConfigure::configure($this, self::$commandParameters);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * {@inheritdoc}
     *
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);

        // Check that roles exists
        $this->checkRoles($output, $input->isInteractive(), $io);

        /** @var FormHelper $helper */
        $helper = $this->getHelper('form');

        /** @var UserGroupDto $dto */
        $dto = $helper->interactUsingForm(UserGroupType::class, $input, $output);

        // Create new user group
        $this->userGroupResource->create($dto);

        if ($input->isInteractive()) {
            $io->success('User group created - have a nice day');
        }

        return 0;
    }

    /**
     * Method to check if database contains role(s), if non exists method will
     * run 'user:create-roles' command which creates all roles to database so
     * that user groups can be created.
     *
     * @throws Throwable
     */
    private function checkRoles(OutputInterface $output, bool $interactive, SymfonyStyle $io): void
    {
        if ($this->roleRepository->countAdvanced() !== 0) {
            return;
        }

        if ($interactive) {
            $io->block('Roles are not yet created, creating those now...');
        }

        $command = $this->getApplication()->find('user:create-roles');

        $arguments = [
            'command' => 'user:create-roles',
        ];

        $input = new ArrayInput($arguments);

        $command->run($input, $output);
    }
}

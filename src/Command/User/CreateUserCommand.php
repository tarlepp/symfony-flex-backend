<?php
declare(strict_types = 1);
/**
 * /src/Command/User/CreateUserCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\User;

use App\Command\HelperConfigure;
use App\DTO\User as UserDto;
use App\Form\Type\Console\UserType;
use App\Repository\RoleRepository;
use App\Resource\UserGroupResource;
use App\Resource\UserResource;
use App\Security\RolesService;
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
     * @var array
     */
    private static $commandParameters = [
        [
            'name'          => 'username',
            'description'   => 'Username',
        ],
        [
            'name'          => 'firstname',
            'description'   => 'Firstname of the user',
        ],
        [
            'name'          => 'surname',
            'description'   => 'Surname of the user',
        ],
        [
            'name'          => 'email',
            'description'   => 'Email of the user',
        ],
        [
            'name'          => 'plainPassword',
            'description'   => 'Plain password for user',
        ],
        [
            'name'          => 'userGroups',
            'description'   => 'User groups where to attach user',
        ],
    ];

    /**
     * @var UserResource
     */
    private $userResource;

    /**
     * @var UserGroupResource
     */
    private $userGroupResource;

    /**
     * @var RolesService
     */
    private $roles;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    /**
     * CreateUserCommand constructor.
     *
     * @param UserResource      $userResource
     * @param UserGroupResource $userGroupResource
     * @param RolesService      $roles
     * @param RoleRepository    $roleRepository
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(
        UserResource $userResource,
        UserGroupResource $userGroupResource,
        RolesService $roles,
        RoleRepository $roleRepository
    ) {
        parent::__construct('user:create');

        $this->userResource = $userResource;
        $this->userGroupResource = $userGroupResource;
        $this->roles = $roles;
        $this->roleRepository = $roleRepository;

        $this->setDescription('Console command to create user to database');
    }

    /**
     * Configures the current command.
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure(): void
    {
        HelperConfigure::configure($this, self::$commandParameters);
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
     * @throws \Exception
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $io->write("\033\143");

        // Check that roles exists
        $this->checkUserGroups($output, $input->isInteractive(), $io);

        /** @var UserDto $dto */
        $dto = $this->getHelper('form')->interactUsingForm(UserType::class, $input, $output);

        // Create new user group
        $this->userResource->create($dto);

        if ($input->isInteractive()) {
            $io->success('User created - have a nice day');
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
     * @param SymfonyStyle    $io
     *
     * @throws \Exception
     *
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    private function checkUserGroups(OutputInterface $output, bool $interactive, SymfonyStyle $io): void
    {
        if ($this->userGroupResource->count() !== 0) {
            return;
        }

        if ($interactive) {
            $io->block('User groups are not yet created, creating those now...');
        }

        // Reset roles
        $this->roleRepository->reset();

        // Create user groups for each role
        $this->createUserGroups($output);
    }

    /**
     * Method to create user groups via existing 'user:create-group' command.
     *
     * @param OutputInterface $output
     *
     * @throws \Exception
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    private function createUserGroups(OutputInterface $output): void
    {
        $command = $this->getApplication()->find('user:create-group');

        // Iterate roles and create user group for each one
        foreach ($this->roles->getRoles() as $role) {
            $arguments = [
                'command' => 'user:create-group',
                '--name'  => $this->roles->getRoleLabel($role),
                '--role'  => $role,
                '-n'      => true,
            ];

            $input = new ArrayInput($arguments);
            $input->setInteractive(false);

            $command->run($input, $output);
        }
    }
}

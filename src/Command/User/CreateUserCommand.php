<?php
declare(strict_types=1);
/**
 * /src/Command/User/CreateUserCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\User;

use App\Form\Console\UserType;
use App\Repository\RoleRepository;
use App\Resource\UserGroupResource;
use App\Resource\UserResource;
use App\Rest\DTO\User as UserDto;
use App\Security\Roles;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
     * @var Roles
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
     * @param UserResource      $userResource
     * @param UserGroupResource $userGroupResource
     * @param Roles             $roles
     * @param RoleRepository    $roleRepository
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(
        $name = null,
        UserResource $userResource,
        UserGroupResource $userGroupResource,
        Roles $roles,
        RoleRepository $roleRepository
    )
    {
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
        /**
         * Lambda iterator function to parse specified inputs.
         *
         * @param array $input
         *
         * @return InputOption
         */
        $iterator = function (array $input) {
            return new InputOption(
                $input['name'],
                $input['shortcut']    ?? null,
                $input['mode']        ?? InputOption::VALUE_OPTIONAL,
                $input['description'] ?? '',
                $input['default']     ?? null
            );
        };

        // Configure command
        $this->setDefinition(new InputDefinition(\array_map($iterator, self::$commandParameters)));
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
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        // Check that roles exists
        $this->checkUserGroups($output, $input->isInteractive());

        /** @var UserDto $dto */
        $dto = $this->getHelper('form')->interactUsingForm(UserType::class, $input, $output);

        // Create new user group
        $this->userResource->create($dto);

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
     *
     * @throws \InvalidArgumentException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
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

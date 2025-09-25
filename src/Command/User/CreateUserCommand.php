<?php
declare(strict_types = 1);
/**
 * /src/Command/User/CreateUserCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\User;

use App\Command\HelperConfigure;
use App\Command\Traits\ApiKeyUserManagementHelperTrait;
use App\Command\Traits\SymfonyStyleTrait;
use App\DTO\User\UserCreate as UserDto;
use App\Form\Type\Console\UserType;
use App\Repository\RoleRepository;
use App\Resource\UserGroupResource;
use App\Resource\UserResource;
use App\Security\RolesService;
use Matthias\SymfonyConsoleForm\Console\Helper\FormHelper;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * @psalm-import-type TInputOption from HelperConfigure
 *
 * @package App\Command\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[AsCommand(
    name: self::NAME,
    description: 'Console command to create user to database',
)]
class CreateUserCommand extends Command
{
    use ApiKeyUserManagementHelperTrait;
    use SymfonyStyleTrait;

    final public const string NAME = 'user:create';

    private const string PARAMETER_NAME = 'name';
    private const string PARAMETER_DESCRIPTION = 'description';

    /**
     * @var list<TInputOption>
     */
    private static array $commandParameters = [
        [
            self::PARAMETER_NAME => 'username',
            self::PARAMETER_DESCRIPTION => 'Username',
        ],
        [
            self::PARAMETER_NAME => 'firstName',
            self::PARAMETER_DESCRIPTION => 'First name of the user',
        ],
        [
            self::PARAMETER_NAME => 'lastName',
            self::PARAMETER_DESCRIPTION => 'Last name of the user',
        ],
        [
            self::PARAMETER_NAME => 'email',
            self::PARAMETER_DESCRIPTION => 'Email of the user',
        ],
        [
            self::PARAMETER_NAME => 'plainPassword',
            self::PARAMETER_DESCRIPTION => 'Plain password for user',
        ],
        [
            self::PARAMETER_NAME => 'userGroups',
            self::PARAMETER_DESCRIPTION => 'User groups where to attach user',
        ],
    ];

    public function __construct(
        private readonly UserResource $userResource,
        private readonly UserGroupResource $userGroupResource,
        private readonly RolesService $rolesService,
        private readonly RoleRepository $roleRepository,
    ) {
        parent::__construct();
    }

    #[Override]
    public function getRolesService(): RolesService
    {
        return $this->rolesService;
    }

    #[Override]
    protected function configure(): void
    {
        parent::configure();

        HelperConfigure::configure($this, self::$commandParameters);
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Throwable
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);

        // Check that roles exists
        $this->checkUserGroups($output, $input->isInteractive(), $io);

        /** @var FormHelper $helper */
        $helper = $this->getHelper('form');

        /** @var UserDto $dto */
        $dto = $helper->interactUsingForm(UserType::class, $input, $output);

        // Create new user
        $this->userResource->create($dto);

        if ($input->isInteractive()) {
            $io->success('User created - have a nice day');
        }

        return 0;
    }

    /**
     * Method to check if database contains user groups, if non exists method
     * will run 'user:create-group' command to create those automatically
     * according to '$this->roles->getRoles()' output. Basically this will
     * automatically create user groups for each role that is defined to
     * application.
     *
     * Also note that if groups are not found method will reset application
     * 'role' table content, so that we can be sure that we can create all
     * groups correctly.
     *
     * @throws Throwable
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
}

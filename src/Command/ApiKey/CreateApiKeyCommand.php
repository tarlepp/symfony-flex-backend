<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/CreateApiKeyCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command\ApiKey;

use App\Command\HelperConfigure;
use App\Command\Traits\ApiKeyUserManagementHelperTrait;
use App\DTO\ApiKey\ApiKeyCreate as ApiKey;
use App\Form\Type\Console\ApiKeyType;
use App\Repository\RoleRepository;
use App\Resource\ApiKeyResource;
use App\Resource\UserGroupResource;
use App\Security\RolesService;
use Matthias\SymfonyConsoleForm\Console\Helper\FormHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Class CreateApiKeyCommand
 *
 * @package App\Command\ApiKey
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CreateApiKeyCommand extends Command
{
    use ApiKeyUserManagementHelperTrait;

    /**
     * @var array<int, array<string, string>>
     */
    private static array $commandParameters = [
        [
            'name' => 'description',
            'description' => 'Description',
        ],
    ];

    private ApiKeyHelper $apiKeyHelper;
    private ApiKeyResource $apiKeyResource;
    private UserGroupResource $userGroupResource;
    private RolesService $rolesService;
    private RoleRepository $roleRepository;

    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private SymfonyStyle $io;

    /**
     * CreateApiKeyCommand constructor.
     */
    public function __construct(
        ApiKeyHelper $apiKeyHelper,
        ApiKeyResource $apiKeyResource,
        UserGroupResource $userGroupResource,
        RolesService $rolesService,
        RoleRepository $roleRepository
    ) {
        parent::__construct('api-key:create');

        $this->apiKeyHelper = $apiKeyHelper;
        $this->apiKeyResource = $apiKeyResource;
        $this->userGroupResource = $userGroupResource;
        $this->rolesService = $rolesService;
        $this->roleRepository = $roleRepository;

        $this->setDescription('Command to create new API key');
    }

    public function getRolesService(): RolesService
    {
        return $this->rolesService;
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
        $this->io = new SymfonyStyle($input, $output);
        $this->io->write("\033\143");

        // Check that user group(s) exists
        $this->checkUserGroups($output, $input->isInteractive());

        /** @var FormHelper $helper */
        $helper = $this->getHelper('form');

        /** @var ApiKey $dto */
        $dto = $helper->interactUsingForm(ApiKeyType::class, $input, $output);

        // Create new API key
        $apiKey = $this->apiKeyResource->create($dto);

        if ($input->isInteractive()) {
            $this->io->success($this->apiKeyHelper->getApiKeyMessage('API key created - have a nice day', $apiKey));
        }

        return 0;
    }

    /**
     * Method to check if database contains user groups, if non exists method will run 'user:create-group' command
     * to create those automatically according to '$this->roles->getRoles()' output. Basically this will automatically
     * create user groups for each role that is defined to application.
     *
     * Also note that if groups are not found method will reset application 'role' table content, so that we can be
     * sure that we can create all groups correctly.
     *
     * @throws Throwable
     */
    private function checkUserGroups(OutputInterface $output, bool $interactive): void
    {
        if ($this->userGroupResource->count() !== 0) {
            return;
        }

        if ($interactive) {
            $this->io->block(['User groups are not yet created, creating those now...']);
        }

        // Reset roles
        $this->roleRepository->reset();

        // Create user groups for each roles
        $this->createUserGroups($output);
    }
}

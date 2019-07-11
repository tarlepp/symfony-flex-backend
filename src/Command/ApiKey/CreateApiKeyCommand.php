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
use App\Entity\ApiKey as ApiKeyEntity;
use App\Form\Type\Console\ApiKeyType;
use App\Repository\RoleRepository;
use App\Resource\ApiKeyResource;
use App\Resource\UserGroupResource;
use App\Security\RolesService;
use Matthias\SymfonyConsoleForm\Console\Helper\FormHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Class CreateApiKeyCommand
 *
 * @package App\Command\ApiKey
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CreateApiKeyCommand extends Command
{
    // Traits
    use ApiKeyUserManagementHelperTrait;

    /**
     * @var array<int, array<string, int|string>>
     */
    private static $commandParameters = [
        [
            'name' => 'description',
            'description' => 'Description',
        ],
    ];

    /**
     * @var ApiKeyHelper
     */
    private $apiKeyHelper;

    /**
     * @var ApiKeyResource
     */
    private $apiKeyResource;

    /**
     * @var UserGroupResource
     */
    private $userGroupResource;

    /**
     * @var RolesService
     */
    private $rolesService;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * CreateApiKeyCommand constructor.
     *
     * @param ApiKeyHelper      $apiKeyHelper
     * @param ApiKeyResource    $apiKeyResource
     * @param UserGroupResource $userGroupResource
     * @param RolesService      $rolesService
     * @param RoleRepository    $roleRepository
     *
     * @throws LogicException
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

    /**
     * Getter for RolesService
     *
     * @return RolesService
     */
    public function getRolesService(): RolesService
    {
        return $this->rolesService;
    }

    /**
     * Configures the current command.
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure(): void
    {
        parent::configure();

        HelperConfigure::configure($this, self::$commandParameters);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return int|null null or 0 if everything went fine, or an error code
     *
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
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
        /** @var ApiKeyEntity $apiKey */
        $apiKey = $this->apiKeyResource->create($dto);

        if ($input->isInteractive()) {
            $this->io->success($this->apiKeyHelper->getApiKeyMessage('API key created - have a nice day', $apiKey));
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

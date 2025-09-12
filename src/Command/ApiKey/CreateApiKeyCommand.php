<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/CreateApiKeyCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\ApiKey;

use App\Command\HelperConfigure;
use App\Command\Traits\ApiKeyUserManagementHelperTrait;
use App\Command\Traits\SymfonyStyleTrait;
use App\DTO\ApiKey\ApiKeyCreate as ApiKey;
use App\Form\Type\Console\ApiKeyType;
use App\Repository\RoleRepository;
use App\Resource\ApiKeyResource;
use App\Resource\UserGroupResource;
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
 * @package App\Command\ApiKey
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[AsCommand(
    name: self::NAME,
    description: 'Command to create new API key',
)]
class CreateApiKeyCommand extends Command
{
    use ApiKeyUserManagementHelperTrait;
    use SymfonyStyleTrait;

    final public const string NAME = 'api-key:create';

    /**
     * @var list<TInputOption>
     */
    private static array $commandParameters = [
        [
            'name' => 'description',
            'description' => 'Description',
        ],
    ];

    public function __construct(
        private readonly ApiKeyHelper $apiKeyHelper,
        private readonly ApiKeyResource $apiKeyResource,
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

        // Check that user group(s) exists
        $this->checkUserGroups($io, $output, $input->isInteractive());

        /** @var FormHelper $helper */
        $helper = $this->getHelper('form');

        /** @var ApiKey $dto */
        $dto = $helper->interactUsingForm(ApiKeyType::class, $input, $output);

        // Create new API key
        $apiKey = $this->apiKeyResource->create($dto);

        if ($input->isInteractive()) {
            $io->success($this->apiKeyHelper->getApiKeyMessage('API key created - have a nice day', $apiKey));
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
    private function checkUserGroups(SymfonyStyle $io, OutputInterface $output, bool $interactive): void
    {
        if ($this->userGroupResource->count() !== 0) {
            return;
        }

        if ($interactive) {
            $io->block(['User groups are not yet created, creating those now...']);
        }

        // Reset roles
        $this->roleRepository->reset();

        // Create user groups for each roles
        $this->createUserGroups($output);
    }
}

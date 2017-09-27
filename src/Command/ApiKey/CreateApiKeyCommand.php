<?php
declare(strict_types=1);
/**
 * /src/Command/ApiKey/CreateApiKeyCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\ApiKey;

use App\DTO\ApiKey;
use App\Form\Type\Console\ApiKeyType;
use App\Repository\RoleRepository;
use App\Resource\ApiKeyResource;
use App\Resource\UserGroupResource;
use App\Security\RolesService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CreateApiKeyCommand
 *
 * @package App\Command\ApiKey
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CreateApiKeyCommand extends Command
{
    /**
     * @var array
     */
    private static $commandParameters = [
        [
            'name'          => 'description',
            'description'   => 'Description',
        ],
    ];

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
     * @param ApiKeyResource    $apiKeyResource
     * @param UserGroupResource $userGroupResource
     * @param RolesService      $rolesService
     * @param RoleRepository    $roleRepository
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(
        ApiKeyResource $apiKeyResource,
        UserGroupResource $userGroupResource,
        RolesService $rolesService,
        RoleRepository $roleRepository
    ) {
        parent::__construct('api-key:create');

        $this->apiKeyResource = $apiKeyResource;
        $this->userGroupResource = $userGroupResource;
        $this->rolesService = $rolesService;
        $this->roleRepository = $roleRepository;

        $this->setDescription('Command to create new API key');
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
        $iterator = function (array $input): InputOption {
            return new InputOption(
                $input['name'],
                $input['shortcut'] ?? null,
                $input['mode'] ?? InputOption::VALUE_OPTIONAL,
                $input['description'] ?? '',
                $input['default'] ?? null
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
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->write("\033\143");

        // Check that user group(s) exists
        $this->checkUserGroups($output, $input->isInteractive());

        /** @var ApiKey $dto */
        $dto = $this->getHelper('form')->interactUsingForm(ApiKeyType::class, $input, $output);

        // Create new API key
        $apiKey = $this->apiKeyResource->create($dto);

        if ($input->isInteractive()) {
            $this->io->success([
                'API key created - have a nice day',
                ' guid: ' . $apiKey->getId() . "\n" . 'token: ' . $apiKey->getToken(),
            ]);
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
            $this->io->block(['User groups are not yet created, creating those now...']);
        }

        // Reset roles
        $this->roleRepository->reset();

        $command = $this->getApplication()->find('user:create-group');

        // Iterate roles and create user group for each one
        foreach ($this->rolesService->getRoles() as $role) {
            $arguments = [
                'command'   => 'user:create-group',
                '--name'    => $this->rolesService->getRoleLabel($role),
                '--role'    => $role,
                '-n'        => true,
            ];

            $input = new ArrayInput($arguments);
            $input->setInteractive(false);

            $command->run($input, $output);
        }
    }
}

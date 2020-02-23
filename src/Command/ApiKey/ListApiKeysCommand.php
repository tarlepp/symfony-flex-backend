<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/ListApiKeysCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command\ApiKey;

use App\Entity\ApiKey;
use App\Entity\UserGroup;
use App\Resource\ApiKeyResource;
use App\Security\RolesService;
use Closure;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use function array_map;
use function implode;
use function sprintf;

/**
 * Class ListApiKeysCommand
 *
 * @package App\Command\ApiKey
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ListApiKeysCommand extends Command
{
    private ApiKeyResource $apiKeyResource;
    private RolesService $rolesService;

    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private SymfonyStyle $io;

    /**
     * ListUsersCommand constructor.
     *
     * @param ApiKeyResource $apiKeyResource
     * @param RolesService   $rolesService
     *
     * @throws LogicException
     */
    public function __construct(ApiKeyResource $apiKeyResource, RolesService $rolesService)
    {
        parent::__construct('api-key:list');

        $this->apiKeyResource = $apiKeyResource;
        $this->rolesService = $rolesService;

        $this->setDescription('Console command to list API keys');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Executes the current command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int 0 if everything went fine, or an exit code
     *
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->write("\033\143");

        $headers = [
            'Id',
            'Token',
            'Description',
            'Groups',
            'Roles (inherited)',
        ];

        $this->io->title('Current API keys');
        $this->io->table($headers, $this->getRows());

        return 0;
    }

    /**
     * Getter method for formatted API key rows for console table.
     *
     * @return mixed[]
     *
     * @throws Throwable
     */
    private function getRows(): array
    {
        return array_map($this->getFormatterApiKey(), $this->apiKeyResource->find(null, ['token' => 'ASC']));
    }

    /**
     * Getter method for API key formatter closure. This closure will format single ApiKey entity for console
     * table.
     *
     * @return Closure
     */
    private function getFormatterApiKey(): Closure
    {
        $userGroupFormatter = static fn (UserGroup $userGroup): string => sprintf(
            '%s (%s)',
            $userGroup->getName(),
            $userGroup->getRole()->getId()
        );

        return fn (ApiKey $apiToken): array => [
            $apiToken->getId(),
            $apiToken->getToken(),
            $apiToken->getDescription(),
            implode(",\n", $apiToken->getUserGroups()->map($userGroupFormatter)->toArray()),
            implode(",\n", $this->rolesService->getInheritedRoles($apiToken->getRoles())),
        ];
    }
}

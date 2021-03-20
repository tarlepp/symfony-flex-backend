<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/ListApiKeysCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\ApiKey;

use App\Entity\ApiKey;
use App\Entity\UserGroup;
use App\Resource\ApiKeyResource;
use App\Security\RolesService;
use Closure;
use Symfony\Component\Console\Command\Command;
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ListApiKeysCommand extends Command
{
    public function __construct(
        private ApiKeyResource $apiKeyResource,
        private RolesService $rolesService,
    ) {
        parent::__construct('api-key:list');

        $this->setDescription('Console command to list API keys');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->write("\033\143");

        $headers = [
            'Id',
            'Token',
            'Description',
            'Groups',
            'Roles (inherited)',
        ];

        $io->title('Current API keys');
        $io->table($headers, $this->getRows());

        return 0;
    }

    /**
     * Getter method for formatted API key rows for console table.
     *
     * @return array<int, string>
     *
     * @throws Throwable
     */
    private function getRows(): array
    {
        return array_map($this->getFormatterApiKey(), $this->apiKeyResource->find(orderBy: ['token' => 'ASC']));
    }

    /**
     * Getter method for API key formatter closure. This closure will format
     * single ApiKey entity for console table.
     */
    private function getFormatterApiKey(): Closure
    {
        $userGroupFormatter = static fn (UserGroup $userGroup): string => sprintf(
            '%s (%s)',
            $userGroup->getName(),
            $userGroup->getRole()->getId(),
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

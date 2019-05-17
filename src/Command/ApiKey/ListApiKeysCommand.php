<?php
declare(strict_types = 1);
/**
 * /src/Command/ApiKey/ListApiKeysCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
    /**
     * @var ApiKeyResource
     */
    private $apiKeyResource;

    /**
     * @var RolesService
     */
    private $rolesService;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * ListUsersCommand constructor.
     *
     * @param ApiKeyResource $apiKeyResource
     * @param RolesService   $rolesService
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->write("\033\143");

        static $headers = [
            'Id',
            'Token',
            'Description',
            'Groups',
            'Roles (inherited)',
        ];

        $this->io->title('Current API keys');
        $this->io->table($headers, $this->getRows());

        return null;
    }

    /**
     * Getter method for formatted API key rows for console table.
     *
     * @return mixed[]
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
        return function (ApiKey $apiToken): array {
            return [
                $apiToken->getId(),
                $apiToken->getToken(),
                $apiToken->getDescription(),
                implode(",\n", $apiToken->getUserGroups()->map($this->getFormatterUserGroup())->toArray()),
                implode(",\n", $this->rolesService->getInheritedRoles($apiToken->getRoles())),
            ];
        };
    }

    /**
     * Getter method for user group formatter closure. This closure will format single UserGroup entity for console
     * table.
     *
     * @return Closure
     */
    private function getFormatterUserGroup(): Closure
    {
        return static function (UserGroup $userGroup): string {
            return sprintf(
                '%s (%s)',
                $userGroup->getName(),
                $userGroup->getRole()->getId()
            );
        };
    }
}

<?php
declare(strict_types = 1);
/**
 * /src/Command/User/ListUsersCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command\User;

use App\Command\Traits\SymfonyStyleTrait;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Resource\UserResource;
use App\Security\RolesService;
use Closure;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_map;
use function implode;
use function sprintf;

/**
 * Class ListUsersCommand
 *
 * @package App\Command\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ListUsersCommand extends Command
{
    // Traits
    use SymfonyStyleTrait;

    /**
     * @var UserResource
     */
    private $userResource;

    /**
     * @var RolesService
     */
    private $roles;

    /**
     * ListUsersCommand constructor.
     *
     * @param UserResource $userResource
     * @param RolesService $roles
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(UserResource $userResource, RolesService $roles)
    {
        parent::__construct('user:list');

        $this->userResource = $userResource;
        $this->roles = $roles;

        $this->setDescription('Console command to list users');
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
        $io = $this->getSymfonyStyle($input, $output);

        static $headers = [
            'Id',
            'Username',
            'Email',
            'Full name',
            'Roles (inherited)',
            'Groups',
        ];

        $io->title('Current users');
        $io->table($headers, $this->getRows());

        return null;
    }

    /**
     * Getter method for formatted user rows for console table.
     *
     * @return mixed[]
     */
    private function getRows(): array
    {
        return array_map($this->getFormatterUser(), $this->userResource->find(null, ['username' => 'ASC']));
    }

    /**
     * Getter method for user formatter closure. This closure will format single User entity for console table.
     *
     * @return Closure
     */
    private function getFormatterUser(): Closure
    {
        return function (User $user): array {
            return [
                $user->getId(),
                $user->getUsername(),
                $user->getEmail(),
                $user->getFirstname() . ' ' . $user->getSurname(),
                implode(",\n", $this->roles->getInheritedRoles($user->getRoles())),
                implode(",\n", $user->getUserGroups()->map($this->formatterUserGroup())->toArray()),
            ];
        };
    }

    /**
     * Getter method for user group formatter closure. This closure will format single UserGroup entity for console
     * table.
     *
     * @return Closure
     */
    private function formatterUserGroup(): Closure
    {
        return function (UserGroup $userGroup): string {
            return sprintf(
                '%s (%s)',
                $userGroup->getName(),
                $userGroup->getRole()->getId()
            );
        };
    }
}

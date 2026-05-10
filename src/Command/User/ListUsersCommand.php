<?php
declare(strict_types = 1);
/**
 * /src/Command/User/ListUsersCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\User;

use App\Command\Traits\SymfonyStyleTrait;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Resource\UserResource;
use App\Security\RolesService;
use Closure;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function implode;
use function sprintf;

/**
 * @package App\Command\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[AsCommand(
    name: self::NAME,
    description: 'Console command to list users',
)]
class ListUsersCommand extends Command
{
    use SymfonyStyleTrait;

    final public const string NAME = 'user:list';

    public function __construct(
        private readonly UserResource $userResource,
        private readonly RolesService $roles,
    ) {
        parent::__construct();
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

        $headers = [
            'Id',
            'Username',
            'Email',
            'Full name',
            'Roles (inherited)',
            'Groups',
        ];

        $io->title('Current users');
        $io->table($headers, $this->getRows());

        return 0;
    }

    /**
     * Getter method for formatted user rows for console table.
     *
     * @return array<int, array<int, string>>
     *
     * @throws Throwable
     */
    private function getRows(): array
    {
        /** @var Closure(User):array<int, string> $formatter */
        $formatter = $this->getFormatterUser();

        /** @var array<int, User> $users */
        $users = $this->userResource->find(orderBy: [
            'username' => 'ASC',
        ]);

        /** @var array<int, array<int, string>> $rows */
        $rows = [];

        foreach ($users as $user) {
            $rows[] = $formatter($user);
        }

        return $rows;
    }

    /**
     * Getter method for user formatter closure. This closure will format
     * single User entity for console table.
     */
    private function getFormatterUser(): Closure
    {
        $userGroupFormatter = static fn (UserGroup $userGroup): string => sprintf(
            '%s (%s)',
            $userGroup->getName(),
            $userGroup->getRole()->getId(),
        );

        return function (User $user) use ($userGroupFormatter): array {
            $formattedUserGroups = [];

            foreach ($user->getUserGroups() as $userGroup) {
                $formattedUserGroups[] = $userGroupFormatter($userGroup);
            }

            return [
                $user->getId(),
                $user->getUsername(),
                $user->getEmail(),
                $user->getFirstName() . ' ' . $user->getLastName(),
                implode(",\n", $this->roles->getInheritedRoles($user->getRoles())),
                implode(",\n", $formattedUserGroups),
            ];
        };
    }
}

<?php
declare(strict_types = 1);
/**
 * /src/Command/User/ListUserGroupsCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\User;

use App\Command\Traits\SymfonyStyleTrait;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
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
    description: 'Console command to list user groups',
)]
class ListUserGroupsCommand extends Command
{
    use SymfonyStyleTrait;

    final public const string NAME = 'user:list-groups';

    public function __construct(
        private readonly UserGroupResource $userGroupResource,
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
            'Name',
            'Role',
            'Users',
        ];

        $io->title('Current user groups');
        $io->table($headers, $this->getRows());

        return 0;
    }

    /**
     * Getter method for formatted user group rows for console table.
     *
     * @return array<int, array<int, string>>
     *
     * @throws Throwable
     */
    private function getRows(): array
    {
        /** @var Closure(UserGroup):array<int, string> $formatter */
        $formatter = $this->getFormatterUserGroup();

        /** @var array<int, UserGroup> $userGroups */
        $userGroups = $this->userGroupResource->find(orderBy: [
            'name' => 'ASC',
        ]);

        /** @var array<int, array<int, string>> $rows */
        $rows = [];

        foreach ($userGroups as $userGroup) {
            $rows[] = $formatter($userGroup);
        }

        return $rows;
    }

    /**
     * Getter method for user group formatter closure. This closure will
     * format single UserGroup entity for console table.
     */
    private function getFormatterUserGroup(): Closure
    {
        $userFormatter = static fn (User $user): string => sprintf(
            '%s %s <%s>',
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
        );

        return static function (UserGroup $userGroup) use ($userFormatter): array {
            $formattedUsers = [];

            foreach ($userGroup->getUsers() as $user) {
                $formattedUsers[] = $userFormatter($user);
            }

            return [
                $userGroup->getId(),
                $userGroup->getName(),
                $userGroup->getRole()->getId(),
                implode(",\n", $formattedUsers),
            ];
        };
    }
}

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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function array_map;
use function implode;
use function sprintf;

/**
 * Class ListUserGroupsCommand
 *
 * @package App\Command\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ListUserGroupsCommand extends Command
{
    use SymfonyStyleTrait;

    public function __construct(
        private UserGroupResource $userGroupResource
    ) {
        parent::__construct('user:list-groups');

        $this->setDescription('Console command to list user groups');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * {@inheritdoc}
     *
     * @throws Throwable
     */
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
     * @return array<int, string>
     *
     * @throws Throwable
     */
    private function getRows(): array
    {
        return array_map($this->getFormatterUserGroup(), $this->userGroupResource->find(null, ['name' => 'ASC']));
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
            $user->getEmail()
        );

        return static fn (UserGroup $userGroup): array => [
            $userGroup->getId(),
            $userGroup->getName(),
            $userGroup->getRole()->getId(),
            implode(",\n", $userGroup->getUsers()->map($userFormatter)->toArray()),
        ];
    }
}

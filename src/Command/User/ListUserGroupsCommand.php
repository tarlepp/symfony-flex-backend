<?php
declare(strict_types = 1);
/**
 * /src/Command/User/ListUserGroupsCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\User;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ListUserGroupsCommand
 *
 * @package App\Command\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ListUserGroupsCommand extends Command
{
    /**
     * @var UserGroupResource
     */
    private $userGroupResource;

    /**
     * ListUserGroupsCommand constructor.
     *
     * @param UserGroupResource $userGroupResource
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(UserGroupResource $userGroupResource)
    {
        parent::__construct('user:list-groups');

        $this->userGroupResource = $userGroupResource;

        $this->setDescription('Console command to list user groups');
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
        $io = new SymfonyStyle($input, $output);
        $io->write("\033\143");

        static $headers = [
            'Id',
            'Name',
            'Role',
            'Users',
        ];

        $io->title('Current user groups');
        $io->table($headers, $this->getRows());

        return null;
    }

    /**
     * Getter method for formatted user group rows for console table.
     *
     * @return mixed[]
     */
    private function getRows(): array
    {
        return \array_map($this->getFormatterUserGroup(), $this->userGroupResource->find(null, ['name' => 'ASC']));
    }

    /**
     * Getter method for user group formatter closure. This closure will format single UserGroup entity for console
     * table.
     *
     * @return \Closure
     */
    private function getFormatterUserGroup(): \Closure
    {
        return function (UserGroup $userGroup): array {
            return [
                $userGroup->getId(),
                $userGroup->getName(),
                $userGroup->getRole()->getId(),
                \implode(",\n", $userGroup->getUsers()->map($this->getFormatterUser())->toArray()),
            ];
        };
    }

    /**
     * Getter method for user formatter closure. This closure will format single User entity for console table.
     *
     * @return \Closure
     */
    private function getFormatterUser(): \Closure
    {
        return function (User $user): string {
            return \sprintf(
                '%s %s <%s>',
                $user->getFirstname(),
                $user->getSurname(),
                $user->getEmail()
            );
        };
    }
}

<?php
declare(strict_types=1);
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
     * @return array
     */
    private function getRows(): array
    {
        /**
         * @param User $user
         *
         * @return string
         */
        $formatterUser = function (User $user): string {
            return \sprintf(
                '%s %s <%s>',
                $user->getFirstname(),
                $user->getSurname(),
                $user->getEmail()
            );
        };

        /**
         * @param UserGroup $userGroup
         *
         * @return array
         */
        $formatterGroup = function (UserGroup $userGroup) use ($formatterUser): array {
            return [
                $userGroup->getId(),
                $userGroup->getName(),
                $userGroup->getRole()->getId(),
                \implode(",\n", $userGroup->getUsers()->map($formatterUser)->toArray()),
            ];
        };

        return \array_map($formatterGroup, $this->userGroupResource->find(null, ['name' => 'ASC']));
    }
}

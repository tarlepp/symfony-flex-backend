<?php
declare(strict_types=1);
/**
 * /src/Command/User/ListUsersCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\User;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Resource\UserResource;
use App\Security\RolesService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ListUsersCommand
 *
 * @package App\Command\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ListUsersCommand extends Command
{
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
        $io = new SymfonyStyle($input, $output);
        $io->write("\033\143");

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
     * @return array
     */
    private function getRows(): array
    {
        /**
         * @param UserGroup $userGroup
         *
         * @return string
         */
        $formatterGroup = function (UserGroup $userGroup): string {
            return \sprintf(
                '%s (%s)',
                $userGroup->getName(),
                $userGroup->getRole()->getId()
            );
        };

        /**
         * @param User $user
         *
         * @return array
         */
        $formatterUser = function (User $user) use ($formatterGroup): array {
            return [
                $user->getId(),
                $user->getUsername(),
                $user->getEmail(),
                $user->getFirstname() . ' ' . $user->getSurname(),
                \implode(",\n", $this->roles->getInheritedRoles($user->getRoles())),
                \implode(",\n", $user->getUserGroups()->map($formatterGroup)->toArray()),
            ];
        };

        return \array_map($formatterUser, $this->userResource->find(null, ['username' => 'ASC']));
    }
}

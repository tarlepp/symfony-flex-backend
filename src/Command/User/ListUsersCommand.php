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
use App\Security\Roles;
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
     * @var Roles
     */
    private $roles;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * ListUserGroupsCommand constructor.
     *
     * @param null|string    $name
     * @param UserResource   $userResource
     * @param Roles          $roles
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct($name = null, UserResource $userResource, Roles $roles)
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
        $this->io = new SymfonyStyle($input, $output);
        $this->io->write(\sprintf("\033\143"));

        static $headers = [
            'Id',
            'Username',
            'Email',
            'Full name',
            'Roles (inherited)',
            'Groups',
        ];

        $this->io->title('Current users');
        $this->io->table($headers, $this->getRows());

        return null;
    }

    /**
     * @return array
     */
    private function getRows(): array
    {
        $formatterGroup = function (UserGroup $userGroup) {
            return \sprintf(
                '%s (%s)',
                $userGroup->getName(),
                $userGroup->getRole()->getId()
            );
        };

        $formatterUser = function (User $user) use ($formatterGroup) {
            return [
                $user->getId(),
                $user->getUsername(),
                $user->getEmail(),
                $user->getFirstname() . ' ' . $user->getSurname(),
                \implode(",\n", $this->roles->getInheritedRoles($user->getRoles())),
                \implode(",\n", \array_map($formatterGroup, $user->getUserGroups()->toArray()))
            ];
        };

        return \array_map($formatterUser, $this->userResource->find(null, ['username' => 'ASC']));
    }
}

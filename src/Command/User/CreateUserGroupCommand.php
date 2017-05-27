<?php
declare(strict_types=1);
/**
 * /src/Command/User/CreateUserGroupCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\User;

use App\Form\Console\UserGroupType;
use App\Repository\RoleRepository;
use App\Resource\UserGroupResource;
use App\Rest\DTO\UserGroup as UserGroupDto;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CreateUserGroupCommand
 *
 * @package App\Command\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CreateUserGroupCommand extends Command
{
    /**
     * @var UserGroupResource
     */
    private $userGroupResource;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * CreateUserGroupCommand constructor.
     *
     * @param null              $name
     * @param UserGroupResource $userGroupResource
     * @param RoleRepository    $roleRepository
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(
        $name = null,
        UserGroupResource $userGroupResource,
        RoleRepository $roleRepository
    )
    {
        parent::__construct('user:createGroup');

        $this->userGroupResource = $userGroupResource;
        $this->roleRepository = $roleRepository;

        $this->setDescription('Console command to create user groups');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws \Exception
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);

        // Check that roles exists
        $this->checkRoles($output);

        /** @var UserGroupDto $dto */
        $dto = $this->getHelper('form')->interactUsingForm(
            UserGroupType::class,
            $input,
            $output
        );

        // Create new user group
        $this->userGroupResource->create($dto);

        $this->io->success('User group created - have a nice day');

        return null;
    }

    /**
     * @param OutputInterface $output
     *
     * @throws \Exception
     * @throws \Symfony\Component\Console\Exception\CommandNotFoundException
     */
    private function checkRoles(OutputInterface $output): void
    {
        if ($this->roleRepository->count([]) !== 0) {
            return;
        }

        $this->io->block([
            'Roles are not yet created, creating those now...'
        ]);

        $command = $this->getApplication()->find('user:create-roles');

        $arguments = [
            'command' => 'user:create-roles',
        ];

        $input = new ArrayInput($arguments);

        $command->run($input, $output);
    }
}

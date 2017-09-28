<?php
declare(strict_types=1);
/**
 * /src/Command/User/EditUserGroupCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\User;

use App\DTO\UserGroup as UserGroupDto;
use App\Form\Type\Console\UserGroupType;
use App\Resource\UserGroupResource;
use App\Entity\UserGroup as UserGroupEntity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class EditUserGroupCommand
 *
 * @package App\Command\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class EditUserGroupCommand extends Command
{
    /**
     * @var UserGroupResource
     */
    private $userGroupResource;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * EditUserGroupCommand constructor.
     *
     * @param UserGroupResource $userGroupResource
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(UserGroupResource $userGroupResource)
    {
        parent::__construct('user:edit-group');

        $this->userGroupResource = $userGroupResource;

        $this->setDescription('Command to edit existing user group');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Executes the current command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->write("\033\143");

        $groupFound = false;

        while (!$groupFound) {
            $userGroup = $this->getUserGroup();

            $message = \sprintf(
                'Is this the group [%s - %s (%s)] which information you want to change?',
                $userGroup->getId(),
                $userGroup->getName(),
                $userGroup->getRole()->getId()
            );

            $groupFound = $this->io->confirm($message, false);
        }

        /** @var UserGroupEntity $userGroup */

        // Load entity to DTO
        $dtoLoaded = new UserGroupDto();
        $dtoLoaded->load($userGroup);

        /** @var UserGroupDto $dtoLoaded */
        $dtoEdit = $this->getHelper('form')->interactUsingForm(
            UserGroupType::class,
            $input,
            $output,
            ['data' => $dtoLoaded]
        );

        // Create new user group
        $this->userGroupResource->update($userGroup->getId(), $dtoEdit);

        if ($input->isInteractive()) {
            $this->io->success('User group updated - have a nice day');
        }

        return null;
    }

    /**
     * @return UserGroupEntity
     */
    private function getUserGroup(): UserGroupEntity
    {
        $choices = [];

        /**
         * Lambda function create user group choices
         *
         * @param UserGroupEntity $userGroup
         */
        $iterator = function (UserGroupEntity $userGroup) use (&$choices): void {
            $choices[$userGroup->getId()] = \sprintf('%s (%s)', $userGroup->getName(), $userGroup->getRole()->getId());
        };

        \array_map($iterator, $this->userGroupResource->find([], ['name' => 'asc']));

        $userGroupId = $this->io->choice(
            'Which user group you want to edit?',
            $choices
        );

        return $this->userGroupResource->findOne($userGroupId);
    }
}

<?php
declare(strict_types = 1);
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
     * @var UserHelper
     */
    private $userHelper;

    /**
     * EditUserGroupCommand constructor.
     *
     * @param UserGroupResource $userGroupResource
     * @param UserHelper        $userHelper
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(UserGroupResource $userGroupResource, UserHelper $userHelper)
    {
        parent::__construct('user:edit-group');

        $this->userGroupResource = $userGroupResource;
        $this->userHelper = $userHelper;

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
        $io = new SymfonyStyle($input, $output);
        $io->write("\033\143");

        $groupFound = false;

        while (!$groupFound) {
            $userGroup = $this->userHelper->getUserGroup($io, 'Which user group you want to edit?');

            if ($userGroup === null) {
                break;
            }

            $message = \sprintf(
                'Is this the group [%s - %s (%s)] which information you want to change?',
                $userGroup->getId(),
                $userGroup->getName(),
                $userGroup->getRole()->getId()
            );

            $groupFound = $io->confirm($message, false);
        }

        /** @var UserGroupEntity $userGroup */
        if ($userGroup instanceof UserGroupEntity) {
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

            // Update user group
            $this->userGroupResource->update($userGroup->getId(), $dtoEdit);

            $message = 'User group updated - have a nice day';
        } else {
            $message = 'Nothing changed - have a nice day';
        }

        if ($input->isInteractive()) {
            $io->success($message);
        }

        return null;
    }
}

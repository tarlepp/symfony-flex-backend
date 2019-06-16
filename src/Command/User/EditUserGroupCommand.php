<?php
declare(strict_types = 1);
/**
 * /src/Command/User/EditUserGroupCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command\User;

use App\Command\Traits\SymfonyStyleTrait;
use App\DTO\UserGroup\UserGroupPatch as UserGroupDto;
use App\Entity\UserGroup as UserGroupEntity;
use App\Form\Type\Console\UserGroupType;
use App\Resource\UserGroupResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class EditUserGroupCommand
 *
 * @package App\Command\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class EditUserGroupCommand extends Command
{
    // Traits
    use SymfonyStyleTrait;

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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = $this->getSymfonyStyle($input, $output);

        $userGroup = $this->userHelper->getUserGroup($io, 'Which user group you want to edit?');

        if ($userGroup instanceof UserGroupEntity) {
            $message = $this->updateUserGroup($input, $output, $userGroup);
        }

        if ($input->isInteractive()) {
            $io->success($message ?? 'Nothing changed - have a nice day');
        }

        return null;
    }

    /**
     * Method to update specified user group entity via specified form.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param UserGroupEntity $userGroup
     *
     * @return string
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function updateUserGroup(
        InputInterface $input,
        OutputInterface $output,
        UserGroupEntity $userGroup
    ): string {
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

        return 'User group updated - have a nice day';
    }
}

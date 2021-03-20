<?php
declare(strict_types = 1);
/**
 * /src/Command/User/EditUserGroupCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\User;

use App\Command\Traits\SymfonyStyleTrait;
use App\DTO\UserGroup\UserGroupPatch as UserGroupDto;
use App\Entity\UserGroup as UserGroupEntity;
use App\Form\Type\Console\UserGroupType;
use App\Resource\UserGroupResource;
use Matthias\SymfonyConsoleForm\Console\Helper\FormHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class EditUserGroupCommand
 *
 * @package App\Command\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class EditUserGroupCommand extends Command
{
    use SymfonyStyleTrait;

    public function __construct(
        private UserGroupResource $userGroupResource,
        private UserHelper $userHelper,
    ) {
        parent::__construct('user:edit-group');

        $this->setDescription('Command to edit existing user group');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);
        $userGroup = $this->userHelper->getUserGroup($io, 'Which user group you want to edit?');
        $message = $userGroup instanceof UserGroupEntity ? $this->updateUserGroup($input, $output, $userGroup) : null;

        if ($input->isInteractive()) {
            $io->success($message ?? 'Nothing changed - have a nice day');
        }

        return 0;
    }

    /**
     * Method to update specified user group entity via specified form.
     *
     * @throws Throwable
     */
    protected function updateUserGroup(
        InputInterface $input,
        OutputInterface $output,
        UserGroupEntity $userGroup,
    ): string {
        // Load entity to DTO
        $dtoLoaded = new UserGroupDto();
        $dtoLoaded->load($userGroup);

        /** @var FormHelper $helper */
        $helper = $this->getHelper('form');

        /** @var UserGroupDto $dtoEdit */
        $dtoEdit = $helper->interactUsingForm(UserGroupType::class, $input, $output, ['data' => $dtoLoaded]);

        // Patch user group
        $this->userGroupResource->patch($userGroup->getId(), $dtoEdit);

        return 'User group updated - have a nice day';
    }
}

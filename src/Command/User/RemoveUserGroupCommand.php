<?php
declare(strict_types = 1);
/**
 * /src/Command/User/RemoveUserGroupCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\User;

use App\Command\Traits\SymfonyStyleTrait;
use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class RemoveUserGroupCommand
 *
 * @package App\Command\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RemoveUserGroupCommand extends Command
{
    use SymfonyStyleTrait;

    public function __construct(
        private UserGroupResource $userGroupResource,
        private UserHelper $userHelper,
    ) {
        parent::__construct('user:remove-group');

        $this->setDescription('Console command to remove existing user group');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);
        $userGroup = $this->userHelper->getUserGroup($io, 'Which user group you want to remove?');
        $message = $userGroup instanceof UserGroup ? $this->delete($userGroup) : null;

        if ($input->isInteractive()) {
            $io->success($message ?? 'Nothing changed - have a nice day');
        }

        return 0;
    }

    /**
     * @throws Throwable
     */
    private function delete(UserGroup $userGroup): string
    {
        $this->userGroupResource->delete($userGroup->getId());

        return'User group removed - have a nice day';
    }
}

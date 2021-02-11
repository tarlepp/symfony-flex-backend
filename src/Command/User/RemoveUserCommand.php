<?php
declare(strict_types = 1);
/**
 * /src/Command/User/RemoveUserCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\User;

use App\Command\Traits\SymfonyStyleTrait;
use App\Entity\User;
use App\Resource\UserResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class RemoveUserCommand
 *
 * @package App\Command\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RemoveUserCommand extends Command
{
    use SymfonyStyleTrait;

    public function __construct(
        private UserResource $userResource,
        private UserHelper $userHelper
    ) {
        parent::__construct('user:remove');

        $this->setDescription('Console command to remove existing user');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * {@inheritdoc}
     *
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);

        // Get user entity
        $user = $this->userHelper->getUser($io, 'Which user you want to remove?');
        $message = null;

        if ($user instanceof User) {
            // Delete user
            $this->userResource->delete($user->getId());

            $message = 'User removed - have a nice day';
        }

        if ($input->isInteractive()) {
            $message ??= 'Nothing changed - have a nice day';

            $io->success($message);
        }

        return 0;
    }
}

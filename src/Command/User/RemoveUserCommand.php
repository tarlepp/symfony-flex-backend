<?php
declare(strict_types = 1);
/**
 * /src/Command/User/RemoveUserCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RemoveUserCommand extends Command
{
    use SymfonyStyleTrait;

    private UserResource $userResource;
    private UserHelper $userHelper;

    /**
     * RemoveUserCommand constructor.
     */
    public function __construct(UserResource $userResource, UserHelper $userHelper)
    {
        parent::__construct('user:remove');

        $this->userResource = $userResource;
        $this->userHelper = $userHelper;

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

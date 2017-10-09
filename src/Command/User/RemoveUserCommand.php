<?php
declare(strict_types=1);
/**
 * /src/Command/User/RemoveUserCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\User;

use App\Entity\User;
use App\Resource\UserResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class RemoveUserCommand
 *
 * @package App\Command\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RemoveUserCommand extends Command
{
    /**
     * @var UserResource
     */
    private $userResource;

    /**
     * @var UserHelper
     */
    private $userHelper;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * RemoveApiKeyCommand constructor.
     *
     * @param UserResource $userResource
     * @param UserHelper   $userHelper
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
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

        $userFound = false;

        while (!$userFound) {
            $user = $this->userHelper->getUser($io, 'Which user you want to remove?');

            $message = \sprintf(
                'Is this the user [%s - %s (%s %s)] which you want to remove?',
                $user->getId(),
                $user->getUsername(),
                $user->getFirstname(),
                $user->getSurname()
            );

            $userFound = $io->confirm($message, false);
        }

        /** @var User $user */

        // Delete API key
        $this->userResource->delete($user->getId());

        if ($input->isInteractive()) {
            $io->success([
                'User removed - have a nice day',
            ]);
        }

        return null;
    }
}

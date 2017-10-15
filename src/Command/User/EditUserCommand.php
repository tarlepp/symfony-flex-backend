<?php
declare(strict_types=1);
/**
 * /src/Command/User/EditUserCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\User;

use App\DTO\User as UserDto;
use App\Entity\User as UserEntity;
use App\Form\Type\Console\UserType;
use App\Resource\UserResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class EditUserCommand
 *
 * @package App\Command\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class EditUserCommand extends Command
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
     * EditUserCommand constructor.
     *
     * @param UserResource $userResource
     * @param UserHelper   $userHelper
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(UserResource $userResource, UserHelper $userHelper)
    {
        parent::__construct('user:edit');

        $this->userResource = $userResource;
        $this->userHelper = $userHelper;

        $this->setDescription('Command to edit existing user');
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

        $userFound = false;

        while (!$userFound) {
            $user = $this->userHelper->getUser($io, 'Which user you want to edit?');

            if ($user === null) {
                break;
            }

            $message = \sprintf(
                'Is this the user [%s - %s (%s %s)] which information you want to change?',
                $user->getId(),
                $user->getUsername(),
                $user->getFirstname(),
                $user->getSurname()
            );

            $userFound = $io->confirm($message, false);
        }

        /** @var UserEntity $user */
        if ($user instanceof UserEntity) {
            // Load entity to DTO
            $dtoLoaded = new UserDto();
            $dtoLoaded->load($user);

            /** @var UserDto $dtoEdit */
            $dtoEdit = $this->getHelper('form')->interactUsingForm(
                UserType::class,
                $input,
                $output,
                ['data' => $dtoLoaded]
            );

            // Update user
            $this->userResource->update($user->getId(), $dtoEdit);

            $message = 'User updated - have a nice day';
        } else {
            $message = 'Nothing changed - have a nice day';
        }

        if ($input->isInteractive()) {
            $io->success($message);
        }

        return null;
    }
}

<?php
declare(strict_types=1);
/**
 * /src/Command/User/EditUserCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\User;

use App\Entity\User as UserEntity;
use App\Form\Console\UserType;
use App\Resource\UserResource;
use App\Rest\DTO\User as UserDto;
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
     * @var SymfonyStyle
     */
    private $io;

    /**
     * EditUserGroupCommand constructor.
     *
     * @param null         $name
     * @param UserResource $userResource
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(
        $name = null,
        UserResource $userResource
    )
    {
        parent::__construct('user:edit');

        $this->userResource = $userResource;
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

        $userFound = false;

        while (!$userFound) {
            $user = $this->getUser();

            $message = \sprintf(
                'Is this the group [%s - %s (%s %s)] which information you want to change?',
                $user->getId(),
                $user->getUsername(),
                $user->getFirstname(),
                $user->getSurname()
            );

            $userFound = $this->io->confirm($message, false);
        }

        /** @var UserEntity $user */

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

        if ($input->isInteractive()) {
            $this->io->success('User updated - have a nice day');
        }

        return null;
    }

    /**
     * @return UserEntity
     */
    private function getUser(): UserEntity
    {
        $choices = [];

        /**
         * Lambda function create user choices
         *
         * @param UserEntity $user
         */
        $iterator = function (UserEntity $user) use (&$choices) {
            $message = \sprintf(
                '%s (%s %s <%s>)',
                $user->getUsername(),
                $user->getFirstname(),
                $user->getSurname(),
                $user->getEmail()
            );

            $choices[$user->getId()] = $message;
        };

        \array_map($iterator, $this->userResource->find([], ['username' => 'asc']));

        $userId = $this->io->choice(
            'Which user you want to edit?',
            $choices
        );

        return $this->userResource->findOne($userId);
    }
}

<?php
declare(strict_types = 1);
/**
 * /src/Command/User/EditUserCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command\User;

use App\Command\Traits\SymfonyStyleTrait;
use App\DTO\User\UserUpdate as UserDto;
use App\Entity\User as UserEntity;
use App\Form\Type\Console\UserType;
use App\Resource\UserResource;
use Matthias\SymfonyConsoleForm\Console\Helper\FormHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Class EditUserCommand
 *
 * @package App\Command\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class EditUserCommand extends Command
{
    use SymfonyStyleTrait;

    private UserResource $userResource;
    private UserHelper $userHelper;

    /**
     * EditUserCommand constructor.
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
     * {@inheritdoc}
     *
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);

        // Get user entity
        $user = $this->userHelper->getUser($io, 'Which user you want to edit?');
        $message = null;

        if ($user instanceof UserEntity) {
            $message = $this->updateUser($input, $output, $user);
        }

        if ($input->isInteractive()) {
            $message ??= 'Nothing changed - have a nice day';

            $io->success($message);
        }

        return 0;
    }

    /**
     * Method to update specified user entity via specified form.
     *
     * @throws Throwable
     */
    private function updateUser(InputInterface $input, OutputInterface $output, UserEntity $user): string
    {
        // Load entity to DTO
        $dtoLoaded = new UserDto();
        $dtoLoaded->load($user);

        /** @var FormHelper $helper */
        $helper = $this->getHelper('form');

        /** @var UserDto $dtoEdit */
        $dtoEdit = $helper->interactUsingForm(UserType::class, $input, $output, ['data' => $dtoLoaded]);

        // Patch user
        $this->userResource->patch($user->getId(), $dtoEdit);

        return 'User updated - have a nice day';
    }
}

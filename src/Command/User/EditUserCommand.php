<?php
declare(strict_types = 1);
/**
 * /src/Command/User/EditUserCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\User;

use App\Command\Traits\SymfonyStyleTrait;
use App\DTO\User\UserUpdate as UserDto;
use App\Entity\User as UserEntity;
use App\Form\Type\Console\UserType;
use App\Resource\UserResource;
use Matthias\SymfonyConsoleForm\Console\Helper\FormHelper;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @package App\Command\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[AsCommand(
    name: self::NAME,
    description: 'Command to edit existing user',
)]
class EditUserCommand extends Command
{
    use SymfonyStyleTrait;

    final public const string NAME = 'user:edit';

    public function __construct(
        private readonly UserResource $userResource,
        private readonly UserHelper $userHelper,
    ) {
        parent::__construct();
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Throwable
     */
    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);
        $user = $this->userHelper->getUser($io, 'Which user you want to edit?');
        $message = $user instanceof UserEntity ? $this->updateUser($input, $output, $user) : null;

        if ($input->isInteractive()) {
            $io->success($message ?? 'Nothing changed - have a nice day');
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
        $dtoEdit = $helper->interactUsingForm(
            UserType::class,
            $input,
            $output,
            [
                'data' => $dtoLoaded,
            ]
        );

        // Patch user
        $this->userResource->patch($user->getId(), $dtoEdit);

        return 'User updated - have a nice day';
    }
}

<?php
declare(strict_types = 1);
/**
 * /src/Command/User/EditUserCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\User;

use App\Command\Traits\SymfonyStyleTrait;
use App\DTO\User as UserDto;
use App\Entity\User as UserEntity;
use App\Form\Type\Console\UserType;
use App\Resource\UserResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class EditUserCommand
 *
 * @package App\Command\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class EditUserCommand extends Command
{
    // Traits
    use SymfonyStyleTrait;

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
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = $this->getSymfonyStyle($input, $output);

        // Get user entity
        $user = $this->userHelper->getUser($io, 'Which user you want to edit?');

        if ($user instanceof UserEntity) {
            $message = $this->updateUser($input, $output, $user);
        }

        if ($input->isInteractive()) {
            $io->success($message ?? 'Nothing changed - have a nice day');
        }

        return null;
    }

    /**
     * Method to update specified user entity via specified form.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param UserEntity      $user
     *
     * @return string
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function updateUser(InputInterface $input, OutputInterface $output, UserEntity $user): string
    {
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

        return 'User updated - have a nice day';
    }
}

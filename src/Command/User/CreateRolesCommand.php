<?php
declare(strict_types = 1);
/**
 * /src/Command/User/CreateRolesCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command\User;

use App\Command\Traits\SymfonyStyleTrait;
use App\Entity\Role;
use App\Enum\Role as RoleEnum;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function array_map;
use function array_sum;
use function sprintf;

/**
 * Class CreateRolesCommand
 *
 * @package App\Command\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class CreateRolesCommand extends Command
{
    use SymfonyStyleTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoleRepository $roleRepository,
    ) {
        parent::__construct('user:create-roles');

        $this->setDescription('Console command to create roles to database');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);

        $created = array_sum(
            array_map(
                fn (RoleEnum $role): int => $this->createRole($role),
                RoleEnum::cases(),
            ),
        );

        $this->entityManager->flush();

        $removed = $this->clearRoles();

        if ($input->isInteractive()) {
            $message = sprintf(
                'Created total of %d role(s) and removed %d role(s) - have a nice day',
                $created,
                $removed,
            );

            $io->success($message);
        }

        return 0;
    }

    /**
     * Method to check if specified role exists on database and if not create
     * and persist it to database.
     *
     * @throws Throwable
     */
    private function createRole(RoleEnum $role): int
    {
        $output = 0;

        if ($this->roleRepository->find($role->value) === null) {
            $entity = new Role($role->value);

            $this->entityManager->persist($entity);

            $output = 1;
        }

        return $output;
    }

    /**
     * Method to clean existing roles from database that does not really
     * exist.
     */
    private function clearRoles(): int
    {
        return (int)$this->roleRepository->createQueryBuilder('role')
            ->delete()
            ->where('role.id NOT IN(:roles)')
            ->setParameter(':roles', RoleEnum::getValues())
            ->getQuery()
            ->execute();
    }
}

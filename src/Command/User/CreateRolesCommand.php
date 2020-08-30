<?php
declare(strict_types = 1);
/**
 * /src/Command/User/CreateRolesCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command\User;

use App\Command\Traits\SymfonyStyleTrait;
use App\Entity\Role;
use App\Repository\RoleRepository;
use App\Security\RolesService;
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CreateRolesCommand extends Command
{
    use SymfonyStyleTrait;

    private EntityManagerInterface $entityManager;
    private RoleRepository $roleRepository;
    private RolesService $rolesService;

    /**
     * CreateRolesCommand constructor.
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        RoleRepository $roleRepository,
        RolesService $rolesService
    ) {
        parent::__construct('user:create-roles');

        $this->entityManager = $entityManager;
        $this->roleRepository = $roleRepository;
        $this->rolesService = $rolesService;

        $this->setDescription('Console command to create roles to database');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getSymfonyStyle($input, $output);

        $created = array_sum(
            array_map(
                fn (string $role): int => $this->createRole($role),
                $this->rolesService->getRoles()
            )
        );

        $this->entityManager->flush();

        $removed = $this->clearRoles($this->rolesService->getRoles());

        if ($input->isInteractive()) {
            $message = sprintf(
                'Created total of %d role(s) and removed %d role(s) - have a nice day',
                $created,
                $removed
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
    private function createRole(string $role): int
    {
        $output = 0;

        if ($this->roleRepository->find($role) === null) {
            $entity = new Role($role);

            $this->entityManager->persist($entity);

            $output = 1;
        }

        return $output;
    }

    /**
     * Method to clean existing roles from database that does not really
     * exists.
     *
     * @param array<int, string> $roles
     */
    private function clearRoles(array $roles): int
    {
        return (int)$this->roleRepository->createQueryBuilder('role')
            ->delete()
            ->where('role.id NOT IN(:roles)')
            ->setParameter(':roles', $roles)
            ->getQuery()
            ->execute();
    }
}

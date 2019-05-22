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
use function array_map;
use function array_sum;
use function sprintf;

/**
 * Class CreateRolesCommand
 *
 * @package App\Command\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CreateRolesCommand extends Command
{
    // Traits
    use SymfonyStyleTrait;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    /**
     * @var RolesService
     */
    private $rolesService;

    /**
     * CreateRolesCommand constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param RoleRepository         $roleRepository
     * @param RolesService           $rolesService
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
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

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return int|null null or 0 if everything went fine, or an error code
     *
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = $this->getSymfonyStyle($input, $output);

        /**
         * @param string $role
         * @return int
         */
        $iterator = function (string $role): int {
            return $this->createRole($role);
        };

        $created = array_sum(array_map($iterator, $this->rolesService->getRoles()));

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

        return null;
    }

    /**
     * Method to check if specified role exists on database and if not create and persist it to database.
     *
     * @param string $role
     *
     * @return int
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
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
     * Method to clean existing roles from database that does not really exists.
     *
     * @param string[] $roles
     *
     * @return int
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

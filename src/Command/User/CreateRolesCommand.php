<?php
declare(strict_types = 1);
/**
 * /src/Command/User/CreateRolesCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\User;

use App\Entity\Role;
use App\Repository\RoleRepository;
use App\Security\RolesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CreateRolesCommand
 *
 * @package App\Command\User
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CreateRolesCommand extends Command
{
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
    private $roles;

    /**
     * CreateRolesCommand constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param RoleRepository         $roleRepository
     * @param RolesService           $roles
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        RoleRepository $roleRepository,
        RolesService $roles
    ) {
        parent::__construct('user:create-roles');

        $this->entityManager = $entityManager;
        $this->roleRepository = $roleRepository;
        $this->roles = $roles;

        $this->setDescription('Console command to create roles to database');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $io->write("\033\143");

        // Create defined roles to database
        $created = \array_sum(\array_map([$this, 'createRole'], $this->roles->getRoles()));

        // Flush changes to database after creation
        $this->entityManager->flush();

        // Clear non-valid roles from database
        $removed = $this->clearRoles($this->roles->getRoles());

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
     * @param array $roles
     *
     * @return int
     */
    private function clearRoles(array $roles): int
    {
        return $this->roleRepository->createQueryBuilder('role')
            ->delete()
            ->where('role.id NOT IN(:roles)')
            ->setParameter(':roles', $roles)
            ->getQuery()
            ->execute();
    }
}

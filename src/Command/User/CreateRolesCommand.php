<?php
declare(strict_types=1);
/**
 * /src/Command/User/CreateRolesCommand.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Command\User;

use App\Entity\Role;
use App\Repository\RoleRepository;
use App\Security\Roles;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class CreateRolesCommand
 *
 * @package App\Command\User
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
     * @var Roles
     */
    private $roles;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * CreateRolesCommand constructor.
     *
     * @param null                   $name
     * @param EntityManagerInterface $entityManager
     * @param RoleRepository         $roleRepository
     * @param Roles                  $roles
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(
        $name = null,
        EntityManagerInterface $entityManager,
        RoleRepository $roleRepository,
        Roles $roles
    )
    {
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
        $this->io = new SymfonyStyle($input, $output);

        // Create defined roles to database
        \array_map([$this, 'createRole'], $this->roles->getRoles());

        // Flush changes to database after creation
        $this->entityManager->flush();

        // Clear non-valid roles from database
        $this->clearRoles($this->roles->getRoles());

        return null;
    }

    /**
     * @param string $role
     */
    private function createRole(string $role): void
    {
        if ($this->roleRepository->find($role) === null) {
            $entity = new Role($role);

            $this->entityManager->persist($entity);
        }
    }

    /**
     * Method to clean existing roles from database that does not really exists.
     *
     * @param array $roles
     */
    private function clearRoles(array $roles): void
    {
        $this->roleRepository->createQueryBuilder('role')
            ->delete()
            ->where('role.role NOT IN(:roles)')
            ->setParameter(':roles', $roles)
            ->getQuery()
            ->execute();
    }
}

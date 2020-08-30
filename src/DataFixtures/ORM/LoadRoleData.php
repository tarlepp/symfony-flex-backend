<?php
declare(strict_types = 1);
/**
 * /src/DataFixtures/ORM/LoadRoleData.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\DataFixtures\ORM;

use App\Entity\Role;
use App\Security\Interfaces\RolesServiceInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Throwable;
use function array_map;

/**
 * Class LoadRoleData
 *
 * @package App\DataFixtures\ORM
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @psalm-suppress MissingConstructor
 */
final class LoadRoleData extends Fixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    private ObjectManager $manager;
    private RolesServiceInterface $roles;

    /**
     * Load data fixtures with the passed EntityManager
     */
    public function load(ObjectManager $manager): void
    {
        /** @var RolesServiceInterface $rolesService */
        $rolesService = $this->container->get('test.app.security.roles_service');

        $this->roles = $rolesService;
        $this->manager = $manager;

        // Create entities
        array_map(fn (string $role): bool => $this->createRole($role), $this->roles->getRoles());

        // Flush database changes
        $this->manager->flush();
    }

    /**
     * Get the order of this fixture
     */
    public function getOrder(): int
    {
        return 1;
    }

    /**
     * Method to create, persist and flush Role entity to database.
     *
     * @throws Throwable
     */
    private function createRole(string $role): bool
    {
        // Create new Role entity
        $entity = new Role($role);
        $entity->setDescription('Description - ' . $role);

        // Persist entity
        $this->manager->persist($entity);

        // Create reference for later usage
        $this->addReference('Role-' . $this->roles->getShort($role), $entity);

        return true;
    }
}

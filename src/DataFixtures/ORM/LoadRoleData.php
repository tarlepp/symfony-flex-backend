<?php
declare(strict_types = 1);
/**
 * /src/DataFixtures/ORM/LoadRoleData.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\DataFixtures\ORM;

use App\Entity\Role;
use App\Security\Interfaces\RolesServiceInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Throwable;
use function array_map;

/**
 * Class LoadRoleData
 *
 * @package App\DataFixtures\ORM
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @psalm-suppress MissingConstructor
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class LoadRoleData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private RolesServiceInterface $rolesService,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function load(ObjectManager $manager): void
    {
        // Create entities
        array_map(fn (string $role): bool => $this->createRole($manager, $role), $this->rolesService->getRoles());

        // Flush database changes
        $manager->flush();
    }

    public function getOrder(): int
    {
        return 1;
    }

    /**
     * Method to create and persist role entity to database.
     *
     * @throws Throwable
     */
    private function createRole(ObjectManager $manager, string $role): bool
    {
        // Create new Role entity
        $entity = (new Role($role))
            ->setDescription('Description - ' . $role);

        // Persist entity
        $manager->persist($entity);

        // Create reference for later usage
        $this->addReference('Role-' . $this->rolesService->getShort($role), $entity);

        return true;
    }
}

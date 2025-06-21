<?php
declare(strict_types = 1);
/**
 * /tests/DataFixtures/ORM/LoadRoleData.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\DataFixtures\ORM;

use App\Entity\Role;
use App\Security\Interfaces\RolesServiceInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;
use Throwable;
use function array_map;

/**
 * @package App\Tests\DataFixtures\ORM
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class LoadRoleData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly RolesServiceInterface $rolesService,
    ) {
    }

    /**
     * @throws Throwable
     */
    #[Override]
    public function load(ObjectManager $manager): void
    {
        // Create entities
        array_map(fn (string $role): bool => $this->createRole($manager, $role), $this->rolesService->getRoles());

        // Flush database changes
        $manager->flush();
    }

    #[Override]
    public function getOrder(): int
    {
        return 1;
    }

    /**
     * Method to create and persist role entity to database.
     *
     * @throws Throwable
     *
     * @param non-empty-string $role
     */
    private function createRole(ObjectManager $manager, string $role): bool
    {
        // Create new Role entity
        $entity = new Role($role)
            ->setDescription('Description - ' . $role);

        // Persist entity
        $manager->persist($entity);

        // Create reference for later usage
        $this->addReference('Role-' . $this->rolesService->getShort($role), $entity);

        return true;
    }
}

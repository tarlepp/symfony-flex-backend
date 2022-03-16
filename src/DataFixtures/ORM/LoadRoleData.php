<?php
declare(strict_types = 1);
/**
 * /src/DataFixtures/ORM/LoadRoleData.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\DataFixtures\ORM;

use App\Entity\Role;
use App\Enum\Role as RoleEnum;
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
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class LoadRoleData extends Fixture implements OrderedFixtureInterface
{
    /**
     * @throws Throwable
     */
    public function load(ObjectManager $manager): void
    {
        // Create entities
        array_map(fn (RoleEnum $role): bool => $this->createRole($manager, $role), RoleEnum::cases());

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
    private function createRole(ObjectManager $manager, RoleEnum $role): bool
    {
        // Create new Role entity
        $entity = (new Role($role->value))
            ->setDescription('Description - ' . $role->getLabel());

        // Persist entity
        $manager->persist($entity);

        // Create reference for later usage
        $this->addReference('Role-' . $role->getShort(), $entity);

        return true;
    }
}

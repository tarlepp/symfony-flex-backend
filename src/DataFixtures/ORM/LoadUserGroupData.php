<?php
declare(strict_types = 1);
/**
 * /src/DataFixtures/ORM/LoadUserGroupData.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\DataFixtures\ORM;

use App\Entity\Role;
use App\Entity\UserGroup;
use App\Rest\UuidHelper;
use App\Security\Interfaces\RolesServiceInterface;
use App\Utils\Tests\PhpUnitUtil;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Throwable;
use function array_map;

/**
 * Class LoadUserGroupData
 *
 * @package App\DataFixtures\ORM
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @psalm-suppress MissingConstructor
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class LoadUserGroupData extends Fixture implements OrderedFixtureInterface
{
    /**
     * @var array<string, string>
     */
    public static array $uuids = [
        'Role-logged' => '10000000-0000-1000-8000-000000000001',
        'Role-api' => '10000000-0000-1000-8000-000000000002',
        'Role-user' => '10000000-0000-1000-8000-000000000003',
        'Role-admin' => '10000000-0000-1000-8000-000000000004',
        'Role-root' => '10000000-0000-1000-8000-000000000005',
    ];

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
        array_map(fn (string $role): bool => $this->createUserGroup($manager, $role), $this->rolesService->getRoles());

        // Flush database changes
        $manager->flush();
    }

    public function getOrder(): int
    {
        return 2;
    }

    /**
     * Method to create UserGroup entity for specified role.
     *
     * @throws Throwable
     */
    private function createUserGroup(ObjectManager $manager, string $role): bool
    {
        /** @var Role $roleReference */
        $roleReference = $this->getReference('Role-' . $this->rolesService->getShort($role));

        // Create new entity
        $entity = new UserGroup();
        $entity->setRole($roleReference);
        $entity->setName($this->rolesService->getRoleLabel($role));

        PhpUnitUtil::setProperty(
            'id',
            UuidHelper::fromString(self::$uuids['Role-' . $this->rolesService->getShort($role)]),
            $entity
        );

        // Persist entity
        $manager->persist($entity);

        // Create reference for later usage
        $this->addReference('UserGroup-' . $this->rolesService->getShort($role), $entity);

        return true;
    }
}

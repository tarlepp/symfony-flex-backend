<?php
declare(strict_types = 1);
/**
 * /src/DataFixtures/ORM/LoadUserGroupData.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Throwable;
use function array_map;

/**
 * Class LoadUserGroupData
 *
 * @package App\DataFixtures\ORM
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @psalm-suppress MissingConstructor
 */
final class LoadUserGroupData extends Fixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var array<string, string>
     */
    public static array $uuids = [
        'Role-logged' => 'f94629ce-c79b-11ea-87d0-0242ac130003',
        'Role-api' => 'fe4df1e0-c79b-11ea-87d0-0242ac130003',
        'Role-user' => '042650e4-c79c-11ea-87d0-0242ac130003',
        'Role-admin' => '08c19fa0-c79c-11ea-87d0-0242ac130003',
        'Role-root' => '0ef6ce9a-c79c-11ea-87d0-0242ac130003',
    ];

    private ObjectManager $manager;
    private RolesServiceInterface $roles;

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @throws Throwable
     */
    public function load(ObjectManager $manager): void
    {
        /** @var RolesServiceInterface $rolesService */
        $rolesService = $this->container->get('test.app.security.roles_service');

        $this->roles = $rolesService;
        $this->manager = $manager;

        // Create entities
        array_map(fn (string $role): bool => $this->createUserGroup($role), $this->roles->getRoles());

        // Flush database changes
        $this->manager->flush();
    }

    /**
     * Get the order of this fixture
     */
    public function getOrder(): int
    {
        return 2;
    }

    /**
     * Method to create UserGroup entity for specified role.
     *
     * @throws Throwable
     */
    private function createUserGroup(string $role): bool
    {
        /** @var Role $roleReference */
        $roleReference = $this->getReference('Role-' . $this->roles->getShort($role));

        // Create new entity
        $entity = new UserGroup();
        $entity->setRole($roleReference);
        $entity->setName($this->roles->getRoleLabel($role));

        PhpUnitUtil::setProperty(
            'id',
            UuidHelper::fromString(self::$uuids['Role-' . $this->roles->getShort($role)]),
            $entity
        );

        // Persist entity
        $this->manager->persist($entity);

        // Create reference for later usage
        $this->addReference('UserGroup-' . $this->roles->getShort($role), $entity);

        return true;
    }
}

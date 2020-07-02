<?php
declare(strict_types = 1);
/**
 * /src/DataFixtures/ORM/LoadUserData.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\DataFixtures\ORM;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Security\Interfaces\RolesServiceInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Throwable;
use function array_map;

/**
 * Class LoadUserData
 *
 * @package App\DataFixtures\ORM
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
final class LoadUserData extends Fixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

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
        array_map([$this, 'createUser'], $this->roles->getRoles());

        $this->createUser();

        // Flush database changes
        $this->manager->flush();
    }

    /**
     * Get the order of this fixture
     */
    public function getOrder(): int
    {
        return 3;
    }

    /**
     * Method to create User entity with specified role.
     *
     * @throws Throwable
     */
    private function createUser(?string $role = null): void
    {
        $suffix = $role === null ? '' : '-' . $this->roles->getShort($role);

        // Create new entity
        $entity = new User();
        $entity->setUsername('john' . $suffix);
        $entity->setFirstName('John');
        $entity->setLastName('Doe');
        $entity->setEmail('john.doe' . $suffix . '@test.com');
        $entity->setPlainPassword('password' . $suffix);

        if ($role !== null) {
            /** @var UserGroup $userGroup */
            $userGroup = $this->getReference('UserGroup-' . $this->roles->getShort($role));

            $entity->addUserGroup($userGroup);
        }

        // Persist entity
        $this->manager->persist($entity);

        // Create reference for later usage
        $this->addReference('User-' . $entity->getUsername(), $entity);
    }
}

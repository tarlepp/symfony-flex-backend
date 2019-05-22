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
use App\Security\RolesServiceInterface;
use BadMethodCallException;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function array_map;

/**
 * Class LoadUserData
 *
 * @package App\DataFixtures\ORM
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
final class LoadUserData extends Fixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var RolesServiceInterface
     */
    private $roles;

    /**
     * Setter for container.
     *
     * @param ContainerInterface|null $container
     */
    public function setContainer(?ContainerInterface $container = null): void
    {
        if ($container !== null) {
            $this->container = $container;
        }
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     *
     * @throws BadMethodCallException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function load(ObjectManager $manager): void
    {
        /** @var RolesServiceInterface $roles */
        $roles = $this->container->get('test.App\Security\RolesService');

        $this->roles = $roles;
        $this->manager = $manager;

        // Create entities
        array_map([$this, 'createUser'], $this->roles->getRoles());

        $this->createUser();

        // Flush database changes
        $this->manager->flush();
    }

    /**
     * Get the order of this fixture
     *
     * @return int
     */
    public function getOrder(): int
    {
        return 3;
    }

    /**
     * Method to create User entity with specified role.
     *
     * @param string|null $role
     *
     * @throws BadMethodCallException
     */
    private function createUser(?string $role = null): void
    {
        $suffix = $role === null ? '' : '-' . $this->roles->getShort($role);

        // Create new entity
        $entity = new User();
        $entity->setUsername('john' . $suffix);
        $entity->setFirstname('John');
        $entity->setSurname('Doe');
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

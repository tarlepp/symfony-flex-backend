<?php
declare(strict_types = 1);
/**
 * /src/DataFixtures/ORM/LoadUserGroupData.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\DataFixtures\ORM;

use App\Entity\Role;
use App\Entity\UserGroup;
use App\Security\RolesService;
use App\Security\RolesServiceInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function array_map;

/**
 * Class LoadUserGroupData
 *
 * @package App\DataFixtures\ORM
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoadUserGroupData extends Fixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        $this->container = $container;
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     *
     * @throws \BadMethodCallException
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $this->roles = $this->container->get('test.service_locator')->get(RolesService::class);

        $iterator = function (string $role): void {
            $this->createUserGroup($role);
        };

        // Create entities
        array_map($iterator, $this->roles->getRoles());

        // Flush database changes
        $this->manager->flush();
    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    public function getOrder(): int
    {
        return 2;
    }

    /**
     * Method to create UserGroup entity for specified role.
     *
     * @param string $role
     *
     * @throws \BadMethodCallException
     * @throws \Doctrine\Common\DataFixtures\BadMethodCallException
     */
    private function createUserGroup(string $role): void
    {
        /** @var Role $roleReference */
        $roleReference = $this->getReference('Role-' . $this->roles->getShort($role));

        // Create new entity
        $entity = new UserGroup();
        $entity->setRole($roleReference);
        $entity->setName($this->roles->getRoleLabel($role));

        // Persist entity
        $this->manager->persist($entity);

        // Create reference for later usage
        $this->addReference('UserGroup-' . $this->roles->getShort($role), $entity);
    }
}

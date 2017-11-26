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
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadUserGroupData
 *
 * @package App\DataFixtures\ORM
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoadUserGroupData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $this->roles = $this->container->get(RolesService::class);

        // Create entities
        \array_map([$this, 'createUserGroup'], $this->roles->getRoles());

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

<?php
declare(strict_types=1);
/**
 * /src/DataFixtures/ORM/LoadUserGroupData.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\DataFixtures\ORM;

use App\Entity\Role;
use App\Entity\UserGroup;
use App\Security\Roles;
use App\Security\RolesInterface;
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
     * @var RolesInterface
     */
    private $roles;

    /**
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
        $this->roles = $this->container->get(Roles::class);

        \array_map([$this, 'createUserGroup'], $this->roles->getRoles());
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
     * @param string $role
     */
    private function createUserGroup(string $role): void
    {
        /** @var Role $roleReference */
        $roleReference = $this->getReference('Role-' . $role);

        // Create new entity
        $entity = new UserGroup();
        $entity->setRole($roleReference);
        $entity->setName($this->roles->getRoleLabel($role));

        // Persist and flush entity
        $this->manager->persist($entity);
        $this->manager->flush();

        // Create reference for later usage
        $this->addReference('UserGroup-' . $role, $entity);
    }
}

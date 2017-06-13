<?php
declare(strict_types=1);
/**
 * /src/DataFixtures/ORM/LoadUserData.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\DataFixtures\ORM;

use App\Entity\User;
use App\Entity\UserGroup;
use App\Security\Roles;
use App\Security\RolesInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadUserData
 *
 * @package App\DataFixtures\ORM
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoadUserData  extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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

        \array_map([$this, 'createUser'], $this->roles->getRoles());

        $this->createUser();
    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    public function getOrder(): int
    {
        return 3;
    }

    /**
     * @param string $role
     */
    private function createUser(string $role = null): void
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
            $userGroup = $this->getReference('UserGroup-' . $role);

            $entity->addUserGroup($userGroup);
        }

        // Persist and flush entity
        $this->manager->persist($entity);
        $this->manager->flush();

        // Create reference for later usage
        $this->addReference('User-' . $entity->getUsername(), $entity);
    }
}

<?php
declare(strict_types = 1);
/**
 * /src/DataFixtures/ORM/LoadApiKeyData.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\DataFixtures\ORM;

use App\Entity\ApiKey;
use App\Entity\UserGroup;
use App\Security\RolesService;
use App\Security\RolesServiceInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadApiKeyData
 *
 * @package App\DataFixtures\ORM
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoadApiKeyData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        \array_map([$this, 'createApiKey'], $this->roles->getRoles());

        $this->createApiKey();

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
        return 4;
    }

    /**
     * Helper method to create new ApiKey entity with specified role.
     *
     * @param string $role
     */
    private function createApiKey(string $role = null): void
    {
        // Create new entity
        $entity = new ApiKey();
        $entity->setDescription('ApiKey Description: ' . ($role === null ? '' : $this->roles->getShort($role)));
        $entity->setToken(
            \str_pad(($role === null ? '' : $this->roles->getShort($role)), 40, '_')
        );

        $suffix = '';

        if ($role !== null) {
            /** @var UserGroup $userGroup */
            $userGroup = $this->getReference('UserGroup-' . $this->roles->getShort($role));

            $entity->addUserGroup($userGroup);

            $suffix = '-' . $this->roles->getShort($role);
        }

        // Persist entity
        $this->manager->persist($entity);

        // Create reference for later usage
        $this->addReference('ApiKey' . $suffix, $entity);
    }
}

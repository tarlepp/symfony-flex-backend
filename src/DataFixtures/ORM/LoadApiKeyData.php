<?php
declare(strict_types = 1);
/**
 * /src/DataFixtures/ORM/LoadApiKeyData.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\DataFixtures\ORM;

use App\Entity\ApiKey;
use App\Entity\UserGroup;
use App\Security\Interfaces\RolesServiceInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Throwable;
use function array_map;
use function str_pad;

/**
 * Class LoadApiKeyData
 *
 * @package App\DataFixtures\ORM
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
final class LoadApiKeyData extends Fixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        array_map([$this, 'createApiKey'], $this->roles->getRoles());

        $this->createApiKey();

        // Flush database changes
        $this->manager->flush();
    }

    /**
     * Get the order of this fixture
     */
    public function getOrder(): int
    {
        return 4;
    }

    /**
     * Helper method to create new ApiKey entity with specified role.
     *
     * @throws Throwable
     */
    private function createApiKey(?string $role = null): void
    {
        // Create new entity
        $entity = new ApiKey();
        $entity->setDescription('ApiKey Description: ' . ($role === null ? '' : $this->roles->getShort($role)));
        $entity->setToken(
            str_pad($role === null ? '' : $this->roles->getShort($role), 40, '_')
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

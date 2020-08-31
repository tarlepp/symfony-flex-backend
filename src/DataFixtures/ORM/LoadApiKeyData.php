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
use function str_pad;

/**
 * Class LoadApiKeyData
 *
 * @package App\DataFixtures\ORM
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @psalm-suppress MissingConstructor
 */
final class LoadApiKeyData extends Fixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    private ObjectManager $manager;
    private RolesServiceInterface $roles;

    /**
     * @var array<string, string>
     */
    private array $uuids = [
        '' => 'daffdcdc-c79b-11ea-87d0-0242ac130003',
        '-logged' => '066482a0-c79b-11ea-87d0-0242ac130003',
        '-api' => '0cd106cc-c79b-11ea-87d0-0242ac130003',
        '-user' => '1154e02e-c79b-11ea-87d0-0242ac130003',
        '-admin' => '154ea868-c79b-11ea-87d0-0242ac130003',
        '-root' => '187b35ba-c79b-11ea-87d0-0242ac130003',
    ];

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

        PhpUnitUtil::setProperty(
            'id',
            UuidHelper::fromString($this->uuids[$suffix]),
            $entity
        );

        // Persist entity
        $this->manager->persist($entity);

        // Create reference for later usage
        $this->addReference('ApiKey' . $suffix, $entity);
    }
}

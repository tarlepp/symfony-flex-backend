<?php
declare(strict_types = 1);
/**
 * /src/DataFixtures/ORM/LoadApiKeyData.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\DataFixtures\ORM;

use App\Entity\ApiKey;
use App\Entity\UserGroup;
use App\Enum\Role;
use App\Rest\UuidHelper;
use App\Utils\Tests\PhpUnitUtil;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Throwable;
use function array_map;
use function str_pad;

/**
 * Class LoadApiKeyData
 *
 * @package App\DataFixtures\ORM
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class LoadApiKeyData extends Fixture implements OrderedFixtureInterface
{
    /**
     * @var array<string, string>
     */
    private array $uuids = [
        '' => '30000000-0000-1000-8000-000000000001',
        '-logged' => '30000000-0000-1000-8000-000000000002',
        '-api' => '30000000-0000-1000-8000-000000000003',
        '-user' => '30000000-0000-1000-8000-000000000004',
        '-admin' => '30000000-0000-1000-8000-000000000005',
        '-root' => '30000000-0000-1000-8000-000000000006',
    ];

    /**
     * @throws Throwable
     */
    public function load(ObjectManager $manager): void
    {
        // Create entities
        array_map(
            fn (?Role $role): bool => $this->createApiKey($manager, $role),
            [
                null,
                ...Role::cases(),
            ],
        );

        // Flush database changes
        $manager->flush();
    }

    public function getOrder(): int
    {
        return 4;
    }

    /**
     * @throws Throwable
     */
    private function createApiKey(ObjectManager $manager, ?Role $role = null): bool
    {
        // Create new entity
        $entity = (new ApiKey())
            ->setDescription('ApiKey Description: ' . ($role === null ? '' : $role->getShort()))
            ->setToken(str_pad($role === null ? '' : $role->getShort(), 40, '_'));

        $suffix = '';

        if ($role !== null) {
            /** @var UserGroup $userGroup */
            $userGroup = $this->getReference('UserGroup-' . $role->getShort());

            $entity->addUserGroup($userGroup);

            $suffix = '-' . $role->getShort();
        }

        PhpUnitUtil::setProperty(
            'id',
            UuidHelper::fromString($this->uuids[$suffix]),
            $entity
        );

        // Persist entity
        $manager->persist($entity);

        // Create reference for later usage
        $this->addReference('ApiKey' . $suffix, $entity);

        return true;
    }
}

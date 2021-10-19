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
use App\Rest\UuidHelper;
use App\Security\Interfaces\RolesServiceInterface;
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
 * @psalm-suppress MissingConstructor
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

    public function __construct(
        private RolesServiceInterface $rolesService,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function load(ObjectManager $manager): void
    {
        // Create entities
        array_map(
            fn (?string $role): bool => $this->createApiKey($manager, $role),
            [
                null,
                ...$this->rolesService->getRoles(),
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
    private function createApiKey(ObjectManager $manager, ?string $role = null): bool
    {
        // Create new entity
        $entity = (new ApiKey())
            ->setDescription('ApiKey Description: ' . ($role === null ? '' : $this->rolesService->getShort($role)))
            ->setToken(str_pad($role === null ? '' : $this->rolesService->getShort($role), 40, '_'));

        $suffix = '';

        if ($role !== null) {
            /** @var UserGroup $userGroup */
            $userGroup = $this->getReference('UserGroup-' . $this->rolesService->getShort($role));

            $entity->addUserGroup($userGroup);

            $suffix = '-' . $this->rolesService->getShort($role);
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

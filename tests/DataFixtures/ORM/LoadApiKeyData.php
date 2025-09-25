<?php
declare(strict_types = 1);
/**
 * /tests/DataFixtures/ORM/LoadApiKeyData.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\DataFixtures\ORM;

use App\Entity\ApiKey;
use App\Entity\UserGroup;
use App\Rest\UuidHelper;
use App\Security\Interfaces\RolesServiceInterface;
use App\Tests\Utils\PhpUnitUtil;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Override;
use Throwable;
use function array_map;
use function str_pad;

/**
 * @package App\Tests\DataFixtures\ORM
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class LoadApiKeyData extends Fixture implements OrderedFixtureInterface
{
    /**
     * @var array<string, non-empty-string>
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
        private readonly RolesServiceInterface $rolesService,
    ) {
    }

    /**
     * @throws Throwable
     */
    #[Override]
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

    #[Override]
    public function getOrder(): int
    {
        return 4;
    }

    /**
     * @throws Throwable
     */
    private function createApiKey(ObjectManager $manager, ?string $role = null): true
    {
        // Create new entity
        $entity = new ApiKey()
            ->setDescription('ApiKey Description: ' . ($role === null ? '' : $this->rolesService->getShort($role)))
            ->setToken(str_pad($role === null ? '' : $this->rolesService->getShort($role), 40, '_'));

        $suffix = '';

        if ($role !== null) {
            /** @var UserGroup $userGroup */
            $userGroup = $this->getReference('UserGroup-' . $this->rolesService->getShort($role), UserGroup::class);

            $entity->addUserGroup($userGroup);

            $suffix = '-' . $this->rolesService->getShort($role);
        }

        PhpUnitUtil::setProperty(
            'id',
            UuidHelper::fromString($this->uuids[$suffix]),
            $entity,
        );

        // Persist entity
        $manager->persist($entity);

        // Create reference for later usage
        $this->addReference('ApiKey' . $suffix, $entity);

        return true;
    }
}

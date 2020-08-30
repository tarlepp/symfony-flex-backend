<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/ApiKeyTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\ApiKey;
use App\Security\RolesService;
use App\Utils\Tests\StringableArrayObject;
use Generator;
use function array_unique;
use function strlen;

/**
 * Class ApiKeyTest
 *
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @property ApiKey $entity
 */
class ApiKeyTest extends EntityTestCase
{
    protected string $entityName = ApiKey::class;

    public function testThatTokenIsGenerated(): void
    {
        static::assertSame(40, strlen($this->entity->getToken()));
    }

    public function testThatGetRolesContainsExpectedRole(): void
    {
        static::assertContainsEquals(RolesService::ROLE_API, $this->entity->getRoles());
    }

    /**
     * @dataProvider dataProviderTestThatApiKeyHasExpectedRoles
     *
     * @testdox Test that `ApiKey` has expected roles `$expectedRoles` with criteria `$criteria`.
     */
    public function testThatApiKeyHasExpectedRoles(
        StringableArrayObject $expectedRoles,
        StringableArrayObject $criteria
    ): void {
        $apiKey = $this->repository->findOneBy($criteria->getArrayCopy());

        static::assertInstanceOf(ApiKey::class, $apiKey);
        static::assertSame($expectedRoles->getArrayCopy(), $apiKey->getRoles());
    }

    public function dataProviderTestThatApiKeyHasExpectedRoles(): Generator
    {
        static::bootKernel();

        $rolesService = static::$container->get(RolesService::class);

        foreach ($rolesService->getRoles() as $role) {
            yield [
                new StringableArrayObject(array_unique([RolesService::ROLE_API, $role])),
                new StringableArrayObject([
                    'description' => 'ApiKey Description: ' . $rolesService->getShort($role),
                ]),
            ];
        }
    }
}

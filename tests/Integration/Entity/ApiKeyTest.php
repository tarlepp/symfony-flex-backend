<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/ApiKeyTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\ApiKey;
use App\Repository\ApiKeyRepository;
use App\Security\RolesService;
use App\Utils\Tests\StringableArrayObject;
use Generator;
use function array_unique;
use function strlen;

/**
 * Class ApiKeyTest
 *
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method ApiKey getEntity()
 */
class ApiKeyTest extends EntityTestCase
{
    /**
     * @var class-string
     */
    protected string $entityName = ApiKey::class;

    public function testThatTokenIsGenerated(): void
    {
        static::assertSame(40, strlen($this->getEntity()->getToken()));
    }

    public function testThatGetRolesContainsExpectedRole(): void
    {
        static::assertContainsEquals(RolesService::ROLE_API, $this->getEntity()->getRoles());
    }

    /**
     * @dataProvider dataProviderTestThatApiKeyHasExpectedRoles
     *
     * @phpstan-param StringableArrayObject<array<int, string>> $expectedRoles
     * @phpstan-param StringableArrayObject<array<mixed>> $criteria
     * @psalm-param StringableArrayObject $expectedRoles
     * @psalm-param StringableArrayObject $criteria
     *
     * @testdox Test that `ApiKey` has expected roles `$expectedRoles` with criteria `$criteria`.
     */
    public function testThatApiKeyHasExpectedRoles(
        StringableArrayObject $expectedRoles,
        StringableArrayObject $criteria
    ): void {
        static::bootKernel();

        /**
         * @var ApiKeyRepository $repository
         */
        $repository = static::$container->get(ApiKeyRepository::class);

        $apiKey = $repository->findOneBy($criteria->getArrayCopy());

        static::assertInstanceOf(ApiKey::class, $apiKey);
        static::assertSame($expectedRoles->getArrayCopy(), $apiKey->getRoles());
    }

    /**
     * @return Generator<array{0: StringableArrayObject, 1: StringableArrayObject}>
     */
    public function dataProviderTestThatApiKeyHasExpectedRoles(): Generator
    {
        static::bootKernel();

        /**
         * @var RolesService $rolesService
         */
        $rolesService = static::$container->get(RolesService::class);

        foreach ($rolesService->getRoles() as $role) {
            yield [
                new StringableArrayObject(array_unique([RolesService::ROLE_API, $role])),
                new StringableArrayObject(['description' => 'ApiKey Description: ' . $rolesService->getShort($role)]),
            ];
        }
    }
}

<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/ApiKeyTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\ApiKey;
use App\Enum\Role;
use App\Repository\ApiKeyRepository;
use App\Security\RolesService;
use App\Tests\Integration\TestCase\EntityTestCase;
use App\Tests\Utils\StringableArrayObject;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;
use function array_unique;

/**
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method ApiKey getEntity()
 */
final class ApiKeyTest extends EntityTestCase
{
    /**
     * @var class-string
     */
    protected static string $entityName = ApiKey::class;

    /**
     * @throws Throwable
     *
     * @phpstan-param StringableArrayObject<array<int, string>> $expectedRoles
     * @phpstan-param StringableArrayObject<array> $criteria
     * @psalm-param StringableArrayObject $expectedRoles
     * @psalm-param StringableArrayObject $criteria
     */
    #[DataProvider('dataProviderTestThatApiKeyHasExpectedRoles')]
    #[TestDox('Test that `ApiKey` has expected roles `$expectedRoles` with criteria `$criteria`')]
    public function testThatApiKeyHasExpectedRoles(
        StringableArrayObject $expectedRoles,
        StringableArrayObject $criteria
    ): void {
        self::bootKernel();

        $repository = static::getContainer()->get(ApiKeyRepository::class);
        $apiKey = $repository->findOneBy($criteria->getArrayCopy());

        self::assertInstanceOf(ApiKey::class, $apiKey);
        self::assertSame($expectedRoles->getArrayCopy(), $apiKey->getRoles());
    }

    /**
     * @throws Throwable
     *
     * @psalm-return Generator<array{0: StringableArrayObject, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: StringableArrayObject<mixed>}>
     */
    public static function dataProviderTestThatApiKeyHasExpectedRoles(): Generator
    {
        self::bootKernel();

        $rolesService = static::getContainer()->get(RolesService::class);

        foreach ($rolesService->getRoles() as $role) {
            yield [
                new StringableArrayObject(array_unique([Role::API->value, $role])),
                new StringableArrayObject([
                    'description' => 'ApiKey Description: ' . $rolesService->getShort($role),
                ]),
            ];
        }
    }
}

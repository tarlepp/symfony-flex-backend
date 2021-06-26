<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Security/ApiKeyUserTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Security;

use App\Entity\ApiKey;
use App\Resource\UserGroupResource;
use App\Security\ApiKeyUser;
use App\Security\RolesService;
use App\Utils\Tests\StringableArrayObject;
use Exception;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class ApiKeyUserTest
 *
 * @package App\Tests\Unit\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyUserTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatGetRolesReturnsExpected
     *
     * @phpstan-param StringableArrayObject<array<int, string>> $expectedRoles
     * @psalm-param StringableArrayObject $expectedRoles
     *
     * @testdox Test that `$apiKey` has expected roles `$expectedRoles`
     */
    public function testThatGetRolesReturnsExpected(ApiKey $apiKey, StringableArrayObject $expectedRoles): void
    {
        $rolesService = static::getContainer()->get(RolesService::class);

        $apiKeyUser = new ApiKeyUser($apiKey, $rolesService->getInheritedRoles($apiKey->getRoles()));

        static::assertEqualsCanonicalizing($expectedRoles->getArrayCopy(), $apiKeyUser->getRoles());
    }

    /**
     * @psalm-return Generator<array{0: ApiKey, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: ApiKey, 1: StringableArrayObject<mixed>}>
     *
     * @throws Throwable
     */
    public function dataProviderTestThatGetRolesReturnsExpected(): Generator
    {
        $userGroupResource = static::getContainer()->get(UserGroupResource::class);

        yield [new ApiKey(), new StringableArrayObject(['ROLE_API', 'ROLE_LOGGED'])];

        $exception = new Exception('Cannot find user group');

        yield [
            (new ApiKey())->addUserGroup($userGroupResource->findOneBy(['name' => 'Normal users']) ?? throw $exception),
            new StringableArrayObject(['ROLE_API', 'ROLE_USER', 'ROLE_LOGGED']),
        ];

        yield [
            (new ApiKey())->addUserGroup($userGroupResource->findOneBy(['name' => 'Admin users']) ?? throw $exception),
            new StringableArrayObject(['ROLE_API', 'ROLE_ADMIN', 'ROLE_LOGGED', 'ROLE_USER']),
        ];

        yield [
            (new ApiKey())->addUserGroup($userGroupResource->findOneBy(['name' => 'Root users']) ?? throw $exception),
            new StringableArrayObject(['ROLE_API', 'ROLE_ROOT', 'ROLE_LOGGED', 'ROLE_ADMIN', 'ROLE_USER']),
        ];
    }
}

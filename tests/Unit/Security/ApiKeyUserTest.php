<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Security/ApiKeyUserTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Security;

use App\Entity\ApiKey;
use App\Entity\Role;
use App\Entity\UserGroup;
use App\Resource\UserGroupResource;
use App\Security\ApiKeyUser;
use App\Security\RolesService;
use App\Tests\Utils\StringableArrayObject;
use Exception;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * @package App\Tests\Unit\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyUserTest extends KernelTestCase
{
    /**
     * @phpstan-param StringableArrayObject<array<int, string>> $expectedRoles
     * @psalm-param StringableArrayObject $expectedRoles
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatGetRolesReturnsExpected')]
    #[TestDox('Test that `$apiKey` has expected roles `$expectedRoles`')]
    public function testThatGetRolesReturnsExpected(ApiKey $apiKey, StringableArrayObject $expectedRoles): void
    {
        $rolesService = self::getContainer()->get(RolesService::class);

        $apiKeyUser = new ApiKeyUser($apiKey, $rolesService->getInheritedRoles($apiKey->getRoles()));

        self::assertEqualsCanonicalizing($expectedRoles->getArrayCopy(), $apiKeyUser->getRoles());
    }

    /**
     * @psalm-return Generator<array{0: ApiKey, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: ApiKey, 1: StringableArrayObject<mixed>}>
     *
     * @throws Throwable
     */
    public static function dataProviderTestThatGetRolesReturnsExpected(): Generator
    {
        self::bootKernel();

        $userGroupResource = static::getContainer()->get(UserGroupResource::class);

        yield [
            new ApiKey()->addUserGroup(new UserGroup()->setRole(new Role('ROLE_LOGGED'))),
            new StringableArrayObject(['ROLE_LOGGED', 'ROLE_API']),
        ];

        $exception = new Exception('Cannot find user group');

        yield [
            new ApiKey()
                ->addUserGroup(new UserGroup()->setRole(new Role('ROLE_LOGGED')))
                ->addUserGroup($userGroupResource->findOneBy([
                    'name' => 'Normal users',
                ]) ?? throw $exception),
            new StringableArrayObject(['ROLE_LOGGED', 'ROLE_API', 'ROLE_USER']),
        ];

        yield [
            new ApiKey()
                ->addUserGroup(new UserGroup()->setRole(new Role('ROLE_LOGGED')))
                ->addUserGroup($userGroupResource->findOneBy([
                    'name' => 'Admin users',
                ]) ?? throw $exception),
            new StringableArrayObject(['ROLE_API', 'ROLE_ADMIN', 'ROLE_LOGGED', 'ROLE_USER']),
        ];

        yield [
            new ApiKey()
                ->addUserGroup(new UserGroup()->setRole(new Role('ROLE_LOGGED')))
                ->addUserGroup($userGroupResource->findOneBy([
                    'name' => 'Root users',
                ]) ?? throw $exception),
            new StringableArrayObject(['ROLE_API', 'ROLE_ROOT', 'ROLE_LOGGED', 'ROLE_ADMIN', 'ROLE_USER']),
        ];
    }
}

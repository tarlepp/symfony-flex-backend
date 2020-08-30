<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Security/ApiKeyUserTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\Security;

use App\Entity\ApiKey;
use App\Resource\UserGroupResource;
use App\Security\ApiKeyUser;
use App\Security\RolesService;
use App\Utils\Tests\StringableArrayObject;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class ApiKeyUserTest
 *
 * @package App\Tests\Unit\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyUserTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatGetRolesReturnsExpected
     *
     * @testdox Test that `$apiKey` has expected roles `$expectedRoles`.
     */
    public function testThatGetRolesReturnsExpected(ApiKey $apiKey, StringableArrayObject $expectedRoles): void
    {
        static::bootKernel();

        $apiKeyUser = new ApiKeyUser(
            $apiKey,
            static::$container->get(RolesService::class)->getInheritedRoles($apiKey->getRoles())
        );

        static::assertEqualsCanonicalizing($expectedRoles->getArrayCopy(), $apiKeyUser->getRoles());
    }

    /**
     * @throws Throwable
     */
    public function dataProviderTestThatGetRolesReturnsExpected(): Generator
    {
        static::bootKernel();

        /** @var UserGroupResource $userGroupResource */
        $userGroupResource = static::$container->get(UserGroupResource::class);

        yield [new ApiKey(), new StringableArrayObject(['ROLE_API', 'ROLE_LOGGED'])];

        yield [
            (new ApiKey())->addUserGroup($userGroupResource->findOneBy(['name' => 'Normal users'])),
            new StringableArrayObject(['ROLE_API', 'ROLE_USER', 'ROLE_LOGGED']),
        ];

        yield [
            (new ApiKey())->addUserGroup($userGroupResource->findOneBy(['name' => 'Admin users'])),
            new StringableArrayObject(['ROLE_API', 'ROLE_ADMIN', 'ROLE_LOGGED', 'ROLE_USER']),
        ];

        yield [
            (new ApiKey())->addUserGroup($userGroupResource->findOneBy(['name' => 'Root users'])),
            new StringableArrayObject(['ROLE_API', 'ROLE_ROOT', 'ROLE_LOGGED', 'ROLE_ADMIN', 'ROLE_USER']),
        ];
    }
}

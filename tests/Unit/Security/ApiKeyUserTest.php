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
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class ApiKeyUserTest
 *
 * @package App\Tests\Unit\Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ApiKeyUserTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatGetRolesReturnsExpected
     *
     * @param ApiKey $apiKey
     * @param array  $expectedRoles
     */
    public function testThatGetRolesReturnsExpected(ApiKey $apiKey, array $expectedRoles): void
    {
        static::bootKernel();

        $rolesService = static::$container->get(RolesService::class);

        $apiKeyUser = new ApiKeyUser($apiKey, $rolesService);

        static::assertSame($expectedRoles, $apiKeyUser->getRoles());
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatGetRolesReturnsExpected(): Generator
    {
        static::bootKernel();

        /** @var UserGroupResource $userGroupResource */
        $userGroupResource = static::$container->get(UserGroupResource::class);

        yield [new ApiKey(), ['ROLE_API', 'ROLE_LOGGED']];

        yield [
            (new ApiKey())->addUserGroup($userGroupResource->findOneBy(['name' => 'Normal users'])),
            ['ROLE_API', 'ROLE_USER', 'ROLE_LOGGED'],
        ];

        yield [
            (new ApiKey())->addUserGroup($userGroupResource->findOneBy(['name' => 'Admin users'])),
            ['ROLE_API', 'ROLE_ADMIN', 'ROLE_LOGGED', 'ROLE_USER'],
        ];

        yield [
            (new ApiKey())->addUserGroup($userGroupResource->findOneBy(['name' => 'Root users'])),
            ['ROLE_API', 'ROLE_ROOT', 'ROLE_LOGGED', 'ROLE_ADMIN', 'ROLE_USER'],
        ];
    }
}

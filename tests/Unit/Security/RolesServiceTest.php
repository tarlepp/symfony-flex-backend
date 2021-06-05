<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Security/RolesServiceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Security;

use App\Security\RolesService;
use App\Utils\Tests\StringableArrayObject;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class RolesServiceTest
 *
 * @package App\Tests\Unit\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RolesServiceTest extends KernelTestCase
{
    /**
     * @testdox Test that `RolesService::getHierarchy` method returns expected
     */
    public function testThatGetHierarchyReturnsExpected(): void
    {
        $expected = [
            'ROLE_API' => [
                'ROLE_LOGGED',
            ],
            'ROLE_USER' => [
                'ROLE_LOGGED',
            ],
            'ROLE_ADMIN' => [
                'ROLE_USER',
            ],
            'ROLE_ROOT' => [
                'ROLE_ADMIN',
            ],
        ];

        static::assertSame($expected, $this->getService()->getHierarchy(), 'Roles hierarchy is not expected.');
    }

    /**
     * @testdox Test that `RolesService::getRoles` method returns expected
     */
    public function testThatGetRolesReturnsExpected(): void
    {
        static::assertSame(
            [
                'ROLE_LOGGED',
                'ROLE_USER',
                'ROLE_ADMIN',
                'ROLE_ROOT',
                'ROLE_API',
            ],
            $this->getService()->getRoles(),
            'Returned roles are not expected.'
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetRoleLabelReturnsExpected
     *
     * @testdox Test that `RolesService::getRoleLabel` method returns `$expected` when using `$role` as input
     */
    public function testThatGetRoleLabelReturnsExpected(string $role, string $expected): void
    {
        static::assertSame($expected, $this->getService()->getRoleLabel($role), 'Role label was not expected one.');
    }

    /**
     * @dataProvider dataProviderTestThatGetShortReturnsExpected
     *
     * @testdox Test that `RolesService::getShort` method returns `$expected` when using `$input` as input
     */
    public function testThatGetShortReturnsExpected(string $input, string $expected): void
    {
        static::assertSame($expected, $this->getService()->getShort($input), 'Short role name was not expected');
    }

    /**
     * @dataProvider dataProviderTestThatGetInheritedRolesReturnsExpected
     *
     * @phpstan-param StringableArrayObject<array<int, string>> $expected
     * @phpstan-param StringableArrayObject<array<int, string>> $roles
     * @psalm-param StringableArrayObject $expected
     * @psalm-param StringableArrayObject $roles
     *
     * @testdox Test that `RolesService::getInheritedRoles` method returns `$expected` when using `$roles` as input
     */
    public function testThatGetInheritedRolesReturnsExpected(
        StringableArrayObject $expected,
        StringableArrayObject $roles
    ): void {
        static::assertSame(
            $expected->getArrayCopy(),
            $this->getService()->getInheritedRoles($roles->getArrayCopy()),
            'Inherited roles was not expected'
        );
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatGetRoleLabelReturnsExpected(): Generator
    {
        yield [RolesService::ROLE_LOGGED, 'Logged in users'];
        yield [RolesService::ROLE_USER, 'Normal users'];
        yield [RolesService::ROLE_ADMIN, 'Admin users'];
        yield [RolesService::ROLE_ROOT, 'Root users'];
        yield [RolesService::ROLE_API, 'API users'];
        yield ['Not supported role', 'Unknown - Not supported role'];
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatGetShortReturnsExpected(): Generator
    {
        yield [RolesService::ROLE_LOGGED, 'logged'];
        yield [RolesService::ROLE_USER, 'user'];
        yield [RolesService::ROLE_ADMIN, 'admin'];
        yield [RolesService::ROLE_ROOT, 'root'];
        yield [RolesService::ROLE_API, 'api'];
        yield ['SOME_CUSTOM_ROLE', 'custom_role'];
    }

    /**
     * @return Generator<array{0: StringableArrayObject, 1: StringableArrayObject}>
     */
    public function dataProviderTestThatGetInheritedRolesReturnsExpected(): Generator
    {
        yield [
            new StringableArrayObject([RolesService::ROLE_LOGGED]),
            new StringableArrayObject([RolesService::ROLE_LOGGED]),
        ];

        yield [
            new StringableArrayObject([RolesService::ROLE_USER, RolesService::ROLE_LOGGED]),
            new StringableArrayObject([RolesService::ROLE_USER]),
        ];

        yield [
            new StringableArrayObject([RolesService::ROLE_API, RolesService::ROLE_LOGGED]),
            new StringableArrayObject([RolesService::ROLE_API]),
        ];

        yield [
            new StringableArrayObject([RolesService::ROLE_ADMIN, RolesService::ROLE_USER, RolesService::ROLE_LOGGED]),
            new StringableArrayObject([RolesService::ROLE_ADMIN]),
        ];

        yield [
            new StringableArrayObject([
                RolesService::ROLE_ROOT,
                RolesService::ROLE_ADMIN,
                RolesService::ROLE_USER,
                RolesService::ROLE_LOGGED,
            ]),
            new StringableArrayObject([RolesService::ROLE_ROOT]),
        ];
    }

    private function getService(): RolesService
    {
        /** @var RolesService $rolesService */
        $rolesService = static::getContainer()->get(RolesService::class);

        return $rolesService;
    }
}

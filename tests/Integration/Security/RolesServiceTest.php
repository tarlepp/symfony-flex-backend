<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/RolesServiceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Security;

use App\Security\Interfaces\RolesServiceInterface;
use App\Security\RolesService;
use App\Utils\Tests\StringableArrayObject;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Class RolesServiceTest
 *
 * @package App\Tests\Integration\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RolesServiceTest extends KernelTestCase
{
    /**
     * @testdox Test that `getInheritedRoles(array $roles)` method calls expected service method
     */
    public function testThatGetInheritedRolesMethodCallsExpectedServiceMethod(): void
    {
        $roleHierarchy = $this->getMockBuilder(RoleHierarchyInterface::class)->getMock();

        $roleHierarchy
            ->expects(self::once())
            ->method('getReachableRoleNames')
            ->with(['RoleA', 'RoleB'])
            ->willReturn(['RoleA', 'RoleB', 'RoleC']);

        (new RolesService($roleHierarchy))->getInheritedRoles(['RoleA', 'RoleB']);
    }

    /**
     * @testdox Test that `RolesServiceInterface::getRoles` method returns expected
     */
    public function testThatGetRolesReturnsExpected(): void
    {
        self::assertSame(
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
     * @testdox Test that `RolesServiceInterface::getRoleLabel` method returns `$expected` when using `$role` as input
     */
    public function testThatGetRoleLabelReturnsExpected(string $role, string $expected): void
    {
        self::assertSame($expected, $this->getService()->getRoleLabel($role), 'Role label was not expected one.');
    }

    /**
     * @dataProvider dataProviderTestThatGetShortReturnsExpected
     *
     * @testdox Test that `RolesServiceInterface::getShort` method returns `$expected` when using `$input` as input
     */
    public function testThatGetShortReturnsExpected(string $input, string $expected): void
    {
        self::assertSame($expected, $this->getService()->getShort($input), 'Short role name was not expected');
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
        self::assertSame(
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
        yield [RolesServiceInterface::ROLE_LOGGED, 'Logged in users'];
        yield [RolesServiceInterface::ROLE_USER, 'Normal users'];
        yield [RolesServiceInterface::ROLE_ADMIN, 'Admin users'];
        yield [RolesServiceInterface::ROLE_ROOT, 'Root users'];
        yield [RolesServiceInterface::ROLE_API, 'API users'];
        yield ['Not supported role', 'Unknown - Not supported role'];
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatGetShortReturnsExpected(): Generator
    {
        yield [RolesServiceInterface::ROLE_LOGGED, 'logged'];
        yield [RolesServiceInterface::ROLE_USER, 'user'];
        yield [RolesServiceInterface::ROLE_ADMIN, 'admin'];
        yield [RolesServiceInterface::ROLE_ROOT, 'root'];
        yield [RolesServiceInterface::ROLE_API, 'api'];
        yield ['SOME_CUSTOM_ROLE', 'custom_role'];
    }

    /**
     * @psalm-return Generator<array{0: StringableArrayObject, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: StringableArrayObject<mixed>}>
     */
    public function dataProviderTestThatGetInheritedRolesReturnsExpected(): Generator
    {
        yield [
            new StringableArrayObject([RolesServiceInterface::ROLE_LOGGED]),
            new StringableArrayObject([RolesServiceInterface::ROLE_LOGGED]),
        ];

        yield [
            new StringableArrayObject([RolesServiceInterface::ROLE_USER, RolesServiceInterface::ROLE_LOGGED]),
            new StringableArrayObject([RolesServiceInterface::ROLE_USER]),
        ];

        yield [
            new StringableArrayObject([RolesServiceInterface::ROLE_API, RolesServiceInterface::ROLE_LOGGED]),
            new StringableArrayObject([RolesServiceInterface::ROLE_API]),
        ];

        yield [
            new StringableArrayObject([
                RolesServiceInterface::ROLE_ADMIN,
                RolesServiceInterface::ROLE_USER,
                RolesServiceInterface::ROLE_LOGGED,
            ]),
            new StringableArrayObject([RolesServiceInterface::ROLE_ADMIN]),
        ];

        yield [
            new StringableArrayObject([
                RolesServiceInterface::ROLE_ROOT,
                RolesServiceInterface::ROLE_ADMIN,
                RolesServiceInterface::ROLE_USER,
                RolesServiceInterface::ROLE_LOGGED,
            ]),
            new StringableArrayObject([RolesServiceInterface::ROLE_ROOT]),
        ];
    }

    private function getService(): RolesService
    {
        return self::getContainer()->get(RolesService::class);
    }
}

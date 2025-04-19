<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/RolesServiceTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Security;

use App\Enum\Role;
use App\Security\RolesService;
use App\Tests\Utils\StringableArrayObject;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * @package App\Tests\Integration\Security
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RolesServiceTest extends KernelTestCase
{
    #[TestDox('Test that `getInheritedRoles(array $roles)` method calls expected service method')]
    public function testThatGetInheritedRolesMethodCallsExpectedServiceMethod(): void
    {
        $roleHierarchy = $this->getMockBuilder(RoleHierarchyInterface::class)->getMock();

        $roleHierarchy
            ->expects($this->once())
            ->method('getReachableRoleNames')
            ->with(['RoleA', 'RoleB'])
            ->willReturn(['RoleA', 'RoleB', 'RoleC']);

        new RolesService($roleHierarchy)->getInheritedRoles(['RoleA', 'RoleB']);
    }

    #[TestDox('Test that `RolesServiceInterface::getRoles` method returns expected')]
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

    #[DataProvider('dataProviderTestThatGetRoleLabelReturnsExpected')]
    #[TestDox('Test that `RolesServiceInterface::getRoleLabel` method returns `$expected` when using `$role` as input')]
    public function testThatGetRoleLabelReturnsExpected(string $role, string $expected): void
    {
        self::assertSame($expected, $this->getService()->getRoleLabel($role), 'Role label was not expected one.');
    }

    #[DataProvider('dataProviderTestThatGetShortReturnsExpected')]
    #[TestDox('Test that `RolesServiceInterface::getShort` method returns `$expected` when using `$input` as input')]
    public function testThatGetShortReturnsExpected(string $input, string $expected): void
    {
        self::assertSame($expected, $this->getService()->getShort($input), 'Short role name was not expected');
    }

    /**
     * @phpstan-param StringableArrayObject<array<int, string>> $expected
     * @phpstan-param StringableArrayObject<array<int, string>> $roles
     * @psalm-param StringableArrayObject $expected
     * @psalm-param StringableArrayObject $roles
     */
    #[DataProvider('dataProviderTestThatGetInheritedRolesReturnsExpected')]
    #[TestDox('Test that `RolesService::getInheritedRoles` method returns `$expected` when using `$roles` as input')]
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
    public static function dataProviderTestThatGetRoleLabelReturnsExpected(): Generator
    {
        yield [Role::LOGGED->value, 'Logged in users'];
        yield [Role::USER->value, 'Normal users'];
        yield [Role::ADMIN->value, 'Admin users'];
        yield [Role::ROOT->value, 'Root users'];
        yield [Role::API->value, 'API users'];
        yield ['Not supported role', 'Unknown - Not supported role'];
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public static function dataProviderTestThatGetShortReturnsExpected(): Generator
    {
        yield [Role::LOGGED->value, 'logged'];
        yield [Role::USER->value, 'user'];
        yield [Role::ADMIN->value, 'admin'];
        yield [Role::ROOT->value, 'root'];
        yield [Role::API->value, 'api'];
        yield ['SOME_CUSTOM_ROLE', 'Unknown - SOME_CUSTOM_ROLE'];
    }

    /**
     * @psalm-return Generator<array{0: StringableArrayObject, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: StringableArrayObject<mixed>}>
     */
    public static function dataProviderTestThatGetInheritedRolesReturnsExpected(): Generator
    {
        yield [
            new StringableArrayObject([Role::LOGGED->value]),
            new StringableArrayObject([Role::LOGGED->value]),
        ];

        yield [
            new StringableArrayObject([Role::USER->value, Role::LOGGED->value]),
            new StringableArrayObject([Role::USER->value]),
        ];

        yield [
            new StringableArrayObject([Role::API->value, Role::LOGGED->value]),
            new StringableArrayObject([Role::API->value]),
        ];

        yield [
            new StringableArrayObject([
                Role::ADMIN->value,
                Role::USER->value,
                Role::LOGGED->value,
            ]),
            new StringableArrayObject([Role::ADMIN->value]),
        ];

        yield [
            new StringableArrayObject([
                Role::ROOT->value,
                Role::ADMIN->value,
                Role::USER->value,
                Role::LOGGED->value,
            ]),
            new StringableArrayObject([Role::ROOT->value]),
        ];
    }

    private function getService(): RolesService
    {
        return self::getContainer()->get(RolesService::class);
    }
}

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
     * @psalm-return Generator<array{0: StringableArrayObject, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: StringableArrayObject<mixed>}>
     */
    public function dataProviderTestThatGetInheritedRolesReturnsExpected(): Generator
    {
        yield [
            new StringableArrayObject([Role::LOGGED]),
            new StringableArrayObject([Role::LOGGED]),
        ];

        yield [
            new StringableArrayObject([Role::USER, Role::LOGGED]),
            new StringableArrayObject([Role::USER]),
        ];

        yield [
            new StringableArrayObject([Role::API, Role::LOGGED]),
            new StringableArrayObject([Role::API]),
        ];

        yield [
            new StringableArrayObject([
                Role::ADMIN,
                Role::USER,
                Role::LOGGED,
            ]),
            new StringableArrayObject([Role::ADMIN]),
        ];

        yield [
            new StringableArrayObject([
                Role::ROOT,
                Role::ADMIN,
                Role::USER,
                Role::LOGGED,
            ]),
            new StringableArrayObject([Role::ROOT]),
        ];
    }

    private function getService(): RolesService
    {
        return self::getContainer()->get(RolesService::class);
    }
}

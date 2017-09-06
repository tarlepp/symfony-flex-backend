<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Security/RolesServiceTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Unit\Security;

use App\Security\RolesService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class RolesServiceTest
 *
 * @package App\Tests\Unit\Security
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RolesServiceTest extends KernelTestCase
{
    /**
     * @var RolesService
     */
    protected $service;

    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $this->service = static::$kernel->getContainer()->get(RolesService::class);
    }

    public function testThatGetHierarchyReturnsExpected(): void
    {
        $expected = [
            'ROLE_USER' => [
                'ROLE_LOGGED',
            ],
            'ROLE_ADMIN' => [
                'ROLE_USER',
            ],
            'ROLE_ROOT' => [
                'ROLE_ADMIN',
            ]
        ];

        static::assertSame($expected, $this->service->getHierarchy(), 'Roles hierarchy is not expected.');
    }

    public function testThatGetRolesReturnsExpected(): void
    {
        static::assertSame(
            [
                'ROLE_LOGGED',
                'ROLE_USER',
                'ROLE_ADMIN',
                'ROLE_ROOT',
            ],
            $this->service->getRoles(),
            'Returned roles are not expected.'
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetRoleLabelReturnsExpected
     *
     * @param string $role
     * @param string $expected
     */
    public function testThatGetRoleLabelReturnsExpected(string $role, string $expected): void
    {
        static::assertSame($expected, $this->service->getRoleLabel($role), 'Role label was not expected one.');
    }

    /**
     * @dataProvider dataProviderTestThatGetShortReturnsExpected
     *
     * @param string $input
     * @param string $expected
     */
    public function testThatGetShortReturnsExpected(string $input, string $expected): void
    {
        static::assertSame($expected, $this->service->getShort($input), 'Short role name was not expected');
    }

    /**
     * @dataProvider dataProviderTestThatGetInheritedRolesReturnsExpected
     *
     * @param array $expected
     * @param array $roles
     */
    public function testThatGetInheritedRolesReturnsExpected(array $expected, array $roles): void
    {
        static::assertSame($expected, $this->service->getInheritedRoles($roles), 'Inherited roles was not expected');
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetRoleLabelReturnsExpected(): array
    {
        return [
            [RolesService::ROLE_LOGGED, 'Logged in users'],
            [RolesService::ROLE_USER, 'Normal users'],
            [RolesService::ROLE_ADMIN, 'Admin users'],
            [RolesService::ROLE_ROOT, 'Root users'],
            ['Not supported role', 'Unknown - Not supported role'],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetShortReturnsExpected(): array
    {
        return [
            [RolesService::ROLE_LOGGED, 'logged'],
            [RolesService::ROLE_USER, 'user'],
            [RolesService::ROLE_ADMIN, 'admin'],
            [RolesService::ROLE_ROOT, 'root'],
            ['SOME_CUSTOM_ROLE', 'custom_role']
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatGetInheritedRolesReturnsExpected(): array
    {
        return [
            [
                [RolesService::ROLE_LOGGED],
                [RolesService::ROLE_LOGGED]
            ],
            [
                [RolesService::ROLE_USER, RolesService::ROLE_LOGGED],
                [RolesService::ROLE_USER]
            ],
            [
                [RolesService::ROLE_ADMIN, RolesService::ROLE_USER, RolesService::ROLE_LOGGED],
                [RolesService::ROLE_ADMIN]
            ],
            [
                [RolesService::ROLE_ROOT, RolesService::ROLE_ADMIN, RolesService::ROLE_USER, RolesService::ROLE_LOGGED],
                [RolesService::ROLE_ROOT]
            ],
        ];
    }
}

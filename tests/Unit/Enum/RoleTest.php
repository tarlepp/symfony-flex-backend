<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Enum/RoleTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Enum;

use App\Enum\Role;
use Generator;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class RoleTest
 *
 * @package App\Tests\Unit\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RoleTest extends KernelTestCase
{
    /**
     * @testdox Test that enum has all expected cases
     */
    public function testThatEnumCasesAreExpected(): void
    {
        self::assertSame(
            [
                Role::ROLE_LOGGED,
                Role::ROLE_USER,
                Role::ROLE_ADMIN,
                Role::ROLE_ROOT,
                Role::ROLE_API,
            ],
            Role::cases()
        );
    }

    /**
     * @testdox Test that `Role::getValues()` method returns expected value
     */
    public function testThatGetValuesMethodReturnsExpected(): void
    {
        self::assertSame(['ROLE_LOGGED', 'ROLE_USER', 'ROLE_ADMIN', 'ROLE_ROOT', 'ROLE_API'], Role::getValues());
    }

    /**
     * @testdox Test that `getLabelForRole` method throws exception when invalid role is given
     */
    public function testThatGetLabelForRoleMethodThrowsAnExceptionWithUnknownRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown - some role');

        Role::getLabelForRole('some role');
    }

    /**
     * @testdox Test that `getShortForRole` method throws exception when invalid role is given
     */
    public function testThatGetShortForRoleMethodThrowsAnExceptionWithUnknownRole(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown - some role');

        Role::getShortForRole('some role');
    }

    /**
     * @dataProvider dataProviderTestThatGetLabelForRoleMethodReturnsCorrectLabel
     *
     * @testdox Test that `getLabelForRole` method returns correct label `$expected` when using `$input` as input
     */
    public function testThatGetLabelForRoleMethodReturnsCorrectLabel(string $expected, string|Role $input): void
    {
        self::assertSame($expected, Role::getLabelForRole($input));
    }

    /**
     * @dataProvider dataProviderTestThatGetShortForRoleMethodReturnsCorrectLabel
     *
     * @testdox Test that `getShortForRole` method returns correct label `$expected` when using `$input` as input
     */
    public function testThatGetShortForRoleMethodReturnsCorrectLabel(string $expected, string|Role $input): void
    {
        self::assertSame($expected, Role::getShortForRole($input));
    }

    /**
     * @dataProvider dataProviderTestThatGetLabelMethodWorksAsExpected
     *
     * @testdox Test that `getLabel` method returns `$expected` label when using `$role`
     */
    public function testThatGetLabelMethodWorksAsExpected(string $expected, string $role): void
    {
        self::assertSame($expected, Role::from($role)->getLabel());
    }

    /**
     * @dataProvider dataProviderTestThatGetShortMethodWorksAsExpected
     *
     * @testdox Test that `getShort` method returns `$expected` label when using `$role`
     */
    public function testThatGetShortMethodWorksAsExpected(string $expected, string $role): void
    {
        self::assertSame($expected, Role::from($role)->getShort());
    }

    /**
     * @return Generator<array{0: string, 1: string|Role}>
     */
    public function dataProviderTestThatGetLabelForRoleMethodReturnsCorrectLabel(): Generator
    {
        yield ['Logged in users', 'ROLE_LOGGED'];
        yield ['Logged in users', Role::ROLE_LOGGED];

        yield ['Normal users', 'ROLE_USER'];
        yield ['Normal users', Role::ROLE_USER];

        yield ['Admin users', 'ROLE_ADMIN'];
        yield ['Admin users', Role::ROLE_ADMIN];

        yield ['Root users', 'ROLE_ROOT'];
        yield ['Root users', Role::ROLE_ROOT];

        yield ['API users', 'ROLE_API'];
        yield ['API users', Role::ROLE_API];
    }

    /**
     * @return Generator<array{0: string, 1: string|Role}>
     */
    public function dataProviderTestThatGetShortForRoleMethodReturnsCorrectLabel(): Generator
    {
        yield ['logged', 'ROLE_LOGGED'];
        yield ['logged', Role::ROLE_LOGGED];

        yield ['user', 'ROLE_USER'];
        yield ['user', Role::ROLE_USER];

        yield ['admin', 'ROLE_ADMIN'];
        yield ['admin', Role::ROLE_ADMIN];

        yield ['root', 'ROLE_ROOT'];
        yield ['root', Role::ROLE_ROOT];

        yield ['api', 'ROLE_API'];
        yield ['api', Role::ROLE_API];
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatGetLabelMethodWorksAsExpected(): Generator
    {
        yield ['Logged in users', Role::ROLE_LOGGED->value];
        yield ['Normal users', Role::ROLE_USER->value];
        yield ['Admin users', Role::ROLE_ADMIN->value];
        yield ['Root users', Role::ROLE_ROOT->value];
        yield ['API users', Role::ROLE_API->value];
    }

    /**
     * @return Generator<array{0: string, 1: string}>
     */
    public function dataProviderTestThatGetShortMethodWorksAsExpected(): Generator
    {
        yield ['logged', Role::ROLE_LOGGED->value];
        yield ['user', Role::ROLE_USER->value];
        yield ['admin', Role::ROLE_ADMIN->value];
        yield ['root', Role::ROLE_ROOT->value];
        yield ['api', Role::ROLE_API->value];
    }
}

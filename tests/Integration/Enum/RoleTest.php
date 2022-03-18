<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Enum/RoleTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Enum;

use App\Enum\Role;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class RoleTest
 *
 * @package App\Tests\Integration\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RoleTest extends KernelTestCase
{
    /**
     * @testdox Test that `Role::getValues()` returns expected array of values
     */
    public function testThatGetValuesMethodReturnsExpected(): void
    {
        static::assertSame(['ROLE_LOGGED', 'ROLE_USER', 'ROLE_ADMIN', 'ROLE_ROOT', 'ROLE_API'], Role::getValues());
    }
}

<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Enum/LoginTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Enum;

use App\Enum\Login;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class LoginTest
 *
 * @package App\Tests\Integration\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LoginTest extends KernelTestCase
{
    /**
     * @testdox Test that `Login::getValues()` returns expected array of values
     */
    public function testThatGetValuesMethodReturnsExpected(): void
    {
        static::assertSame(['failure', 'success'], Login::getValues());
    }
}

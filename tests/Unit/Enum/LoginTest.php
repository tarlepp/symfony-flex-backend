<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Enum/LoginTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Enum;

use App\Enum\Login;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class LoginTest
 *
 * @package App\Tests\Unit\Enum
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LoginTest extends KernelTestCase
{
    /**
     * @testdox Test that enum has all expected cases
     */
    public function testThatEnumCasesAreExpected(): void
    {
        self::assertSame(
            [
                Login::FAILURE,
                Login::SUCCESS,
            ],
            Login::cases()
        );
    }
}

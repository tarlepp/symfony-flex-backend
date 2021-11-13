<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Validator/Constraints/UniqueUsernameTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\Validator\Constraints;

use App\Validator\Constraints\UniqueUsername;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UniqueUsernameTest
 *
 * @package App\Tests\Unit\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UniqueUsernameTest extends KernelTestCase
{
    /**
     * @testdox Test that `getTargets` method returns expected
     */
    public function testThatGetTargetsReturnsExpected(): void
    {
        self::assertSame('class', (new UniqueUsername())->getTargets());
    }
}

<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Validator/Constraints/UniqueEmailTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\Validator\Constraints;

use App\Validator\Constraints\UniqueEmail;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UniqueEmailTest
 *
 * @package App\Tests\Unit\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UniqueEmailTest extends KernelTestCase
{
    public function testThatGetTargetsReturnsExpected(): void
    {
        static::assertSame('class', (new UniqueEmail())->getTargets());
    }
}

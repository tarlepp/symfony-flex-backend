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
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UniqueUsernameTest extends KernelTestCase
{
    protected $backupGlobals = false;
    protected $backupStaticAttributes = false;

    public function testThatGetTargetsReturnsExpected(): void
    {
        static::assertSame('class', (new UniqueUsername())->getTargets());
    }
}

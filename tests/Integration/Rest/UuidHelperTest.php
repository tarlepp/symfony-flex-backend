<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/UuidHelperTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Rest;

use App\Rest\UuidHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class UuidHelperTest
 *
 * @package App\Tests\Integration\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class UuidHelperTest extends KernelTestCase
{
    public function testThatGetFactoryReturnsSameInstance(): void
    {
        $factory = UuidHelper::getFactory();

        self::assertSame($factory, UuidHelper::getFactory());
    }
}

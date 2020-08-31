<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Entity/DateDimensionTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\Entity;

use App\Entity\DateDimension;
use DateTime;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class DateDimensionTest
 *
 * @package App\Tests\Unit\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class DateDimensionTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatGetCreatedAtReturnsExpected(): void
    {
        $dateTime = new DateTime('now', new DateTimeZone('UTC'));
        $dateTime->setTime(10, 10, 10);

        $entity = new DateDimension($dateTime);

        static::assertSame($dateTime->format('u'), $entity->getCreatedAt()->format('u'));
    }
}

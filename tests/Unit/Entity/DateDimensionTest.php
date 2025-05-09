<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Entity/DateDimensionTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Entity;

use App\Entity\DateDimension;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use function floor;

/**
 * @package App\Tests\Unit\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class DateDimensionTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    public function testThatConstructorCallsExpectedMethods(): void
    {
        $dateTime = DateTimeImmutable::createFromMutable(
            new DateTime('now', new DateTimeZone('UTC'))->setTime(10, 10, 10)
        );

        $entity = new DateDimension($dateTime);

        self::assertSame($dateTime, $entity->getDate());
        self::assertSame($dateTime->format('U'), $entity->getCreatedAt()->format('U'));
        self::assertSame((int)$dateTime->format('Y'), $entity->getYear());
        self::assertSame((int)$dateTime->format('n'), $entity->getMonth());
        self::assertSame((int)$dateTime->format('j'), $entity->getDay());
        self::assertSame((int)floor(((int)$dateTime->format('n') - 1) / 3) + 1, $entity->getQuarter());
        self::assertSame((int)$dateTime->format('W'), $entity->getWeekNumber());
        self::assertSame((int)$dateTime->format('N'), $entity->getDayNumberOfWeek());
        self::assertSame((int)$dateTime->format('z'), $entity->getDayNumberOfYear());
        self::assertSame((bool)$dateTime->format('L'), $entity->isLeapYear());
        self::assertSame((int)$dateTime->format('o'), $entity->getWeekNumberingYear());
        self::assertSame($dateTime->format('U'), $entity->getUnixTime());
    }
}

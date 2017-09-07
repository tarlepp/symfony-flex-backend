<?php
declare(strict_types=1);
/**
 * /tests/Integration/Entity/DateDimensionTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Entity;

use App\Entity\DateDimension;

/**
 * Class DateDimensionTest
 *
 * @package App\Tests\Integration\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class DateDimensionTest extends EntityTestCase
{
    /**
     * @var string
     */
    protected $entityName = DateDimension::class;

    public function testThatConstructorCallsExpectedMethods(): void
    {
        $dateTime = new \DateTime();

        /** @var DateDimension $entity */
        $entity = new $this->entityName($dateTime);

        static::assertSame($dateTime, $entity->getDate());
        static::assertSame((int)$dateTime->format('Y'), $entity->getYear());
        static::assertSame((int)$dateTime->format('n'), $entity->getMonth());
        static::assertSame((int)$dateTime->format('j'), $entity->getDay());
        static::assertSame((int)\floor(((int)$dateTime->format('n') - 1) / 3) + 1, $entity->getQuarter());
        static::assertSame((int)$dateTime->format('W'), $entity->getWeekNumber());
        static::assertSame((int)$dateTime->format('N'), $entity->getDayNumberOfWeek());
        static::assertSame((int)$dateTime->format('z'), $entity->getDayNumberOfYear());
        static::assertSame((bool)$dateTime->format('L'), $entity->isLeapYear());
        static::assertSame((int)$dateTime->format('o'), $entity->getWeekNumberingYear());
        static::assertSame((int)$dateTime->format('U'), $entity->getUnixTime());
    }
}

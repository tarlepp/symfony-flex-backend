<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/DateDimensionTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\DateDimension;
use App\Entity\Interfaces\EntityInterface;
use App\Utils\Tests\PhpUnitUtil;
use DateTime;
use Exception;
use Throwable;
use function floor;
use function in_array;
use function ucfirst;

/**
 * Class DateDimensionTest
 *
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class DateDimensionTest extends EntityTestCase
{
    protected string $entityName = DateDimension::class;

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @param string $field
     * @param string $type
     * @param array $meta
     *
     * @testdox No setter for `$field` field in read only entity - so cannot test this.
     */
    public function testThatSetterOnlyAcceptSpecifiedType(
        ?string $field = null,
        ?string $type = null,
        ?array $meta = null
    ): void {
        static::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @param string $field
     * @param string $type
     * @param array $meta
     *
     * @testdox No setter for `$field` field in read only entity - so cannot test this.
     */
    public function testThatSetterReturnsInstanceOfEntity(
        ?string $field = null,
        ?string $type = null,
        ?array $meta = null
    ): void {
        static::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @dataProvider dataProviderTestThatSetterAndGettersWorks
     *
     * @throws Throwable
     *
     * @testdox Test that getter method for `$field` with `$type` returns expected.
     */
    public function testThatGetterReturnsExpectedValue(string $field, string $type, array $meta): void
    {
        $getter = 'get' . ucfirst($field);

        if (in_array($type, [PhpUnitUtil::TYPE_BOOL, PhpUnitUtil::TYPE_BOOLEAN], true)) {
            $getter = 'is' . ucfirst($field);
        }

        $dateDimension = new DateDimension(new DateTime());

        try {
            if (static::isType($type)) {
                $method = 'assertIs' . ucfirst($type);

                static::$method($dateDimension->{$getter}());
            }
        } catch (\Throwable $error) {
            static::assertInstanceOf($type, $dateDimension->{$getter}(), $error->getMessage());
        }
    }

    /**
     * @throws Throwable
     */
    public function testThatConstructorCallsExpectedMethods(): void
    {
        $dateTime = new DateTime();

        /** @var DateDimension $entity */
        $entity = new $this->entityName($dateTime);

        static::assertSame($dateTime, $entity->getDate());
        static::assertSame((int)$dateTime->format('Y'), $entity->getYear());
        static::assertSame((int)$dateTime->format('n'), $entity->getMonth());
        static::assertSame((int)$dateTime->format('j'), $entity->getDay());
        static::assertSame((int)floor(((int)$dateTime->format('n') - 1) / 3) + 1, $entity->getQuarter());
        static::assertSame((int)$dateTime->format('W'), $entity->getWeekNumber());
        static::assertSame((int)$dateTime->format('N'), $entity->getDayNumberOfWeek());
        static::assertSame((int)$dateTime->format('z'), $entity->getDayNumberOfYear());
        static::assertSame((bool)$dateTime->format('L'), $entity->isLeapYear());
        static::assertSame((int)$dateTime->format('o'), $entity->getWeekNumberingYear());
        static::assertSame((int)$dateTime->format('U'), $entity->getUnixTime());
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Exception
     */
    protected function getEntity(): EntityInterface
    {
        return new $this->entityName(new DateTime());
    }
}

<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/DateDimensionTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\DateDimension;
use App\Utils\Tests\PhpUnitUtil;
use DateTimeImmutable;
use Throwable;
use function floor;
use function in_array;
use function ucfirst;

/**
 * Class DateDimensionTest
 *
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method DateDimension getEntity()
 */
class DateDimensionTest extends EntityTestCase
{
    /**
     * @var class-string
     */
    protected string $entityName = DateDimension::class;

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @testdox No setter for `$field` field in read only entity - so cannot test this.
     */
    public function testThatSetterOnlyAcceptSpecifiedType(
        ?string $field = null,
        ?string $type = null,
        ?array $meta = null,
    ): void {
        self::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @testdox No setter for `$field` field in read only entity - so cannot test this.
     */
    public function testThatSetterReturnsInstanceOfEntity(
        ?string $field = null,
        ?string $type = null,
        ?array $meta = null
    ): void {
        self::markTestSkipped('There is not setter in read only entity...');
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

        $dateDimension = $this->createEntity();

        try {
            $method = 'assertIs' . ucfirst($type);

            self::$method($dateDimension->{$getter}());
        } catch (Throwable $error) {
            /**
             * @var class-string $type
             */
            self::assertInstanceOf($type, $dateDimension->{$getter}(), $error->getMessage());
        }
    }

    /**
     * @throws Throwable
     */
    public function testThatConstructorCallsExpectedMethods(): void
    {
        $dateTime = new DateTimeImmutable();

        /** @var DateDimension $entity */
        $entity = new $this->entityName($dateTime);

        self::assertSame($dateTime, $entity->getDate());
        self::assertSame((int)$dateTime->format('Y'), $entity->getYear());
        self::assertSame((int)$dateTime->format('n'), $entity->getMonth());
        self::assertSame((int)$dateTime->format('j'), $entity->getDay());
        self::assertSame((int)floor(((int)$dateTime->format('n') - 1) / 3) + 1, $entity->getQuarter());
        self::assertSame((int)$dateTime->format('W'), $entity->getWeekNumber());
        self::assertSame((int)$dateTime->format('N'), $entity->getDayNumberOfWeek());
        self::assertSame((int)$dateTime->format('z'), $entity->getDayNumberOfYear());
        self::assertSame((bool)$dateTime->format('L'), $entity->isLeapYear());
        self::assertSame((int)$dateTime->format('o'), $entity->getWeekNumberingYear());
        self::assertSame((int)$dateTime->format('U'), $entity->getUnixTime());
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function createEntity(): DateDimension
    {
        return new DateDimension(new DateTimeImmutable());
    }
}

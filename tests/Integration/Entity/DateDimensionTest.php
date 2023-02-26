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
use PHPUnit\Framework\Attributes\DataProvider;
use Throwable;
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
    protected static string $entityName = DateDimension::class;

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @testdox No setter for `$property` property in read only entity - so cannot test this
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorks')]
    public function testThatSetterOnlyAcceptSpecifiedType(
        ?string $property = null,
        ?string $type = null,
        ?array $meta = null,
    ): void {
        self::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @testdox No setter for `$property` property in read only entity - so cannot test this
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorks')]
    public function testThatSetterReturnsInstanceOfEntity(
        ?string $property = null,
        ?string $type = null,
        ?array $meta = null
    ): void {
        self::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     *
     * @throws Throwable
     * @testdox Test that getter method for `$type $property` property returns expected
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorks')]
    public function testThatGetterReturnsExpectedValue(string $property, string $type, array $meta): void
    {
        $getter = 'get' . ucfirst($property);

        if (in_array($type, [PhpUnitUtil::TYPE_BOOL, PhpUnitUtil::TYPE_BOOLEAN], true)) {
            $getter = 'is' . ucfirst($property);
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
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function createEntity(): DateDimension
    {
        return new DateDimension(new DateTimeImmutable());
    }
}

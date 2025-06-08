<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/DateDimensionTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\DateDimension;
use App\Tests\Integration\TestCase\EntityTestCase;
use App\Tests\Utils\PhpUnitUtil;
use DateTimeImmutable;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;
use function in_array;
use function ucfirst;

/**
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

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorksWithoutReadOnlyFlag')]
    #[TestDox('No setter for `$property` property in read only entity - so cannot test this')]
    #[Override]
    public function testThatSetterOnlyAcceptSpecifiedType(
        ?string $property = null,
        ?string $type = null,
        ?array $meta = null,
    ): void {
        self::markTestSkipped('There is not setter in read only entity...');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorksWithoutReadOnlyFlag')]
    #[TestDox('No setter for `$property` property in read only entity - so cannot test this')]
    #[Override]
    public function testThatSetterReturnsInstanceOfEntity(
        ?string $property = null,
        ?string $type = null,
        ?array $meta = null
    ): void {
        self::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorksWithoutReadOnlyFlag')]
    #[TestDox('Test that getter method for `$type $property` property returns expected')]
    #[Override]
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
    #[Override]
    protected function createEntity(): DateDimension
    {
        return new DateDimension(new DateTimeImmutable());
    }
}

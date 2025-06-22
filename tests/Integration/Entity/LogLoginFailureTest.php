<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/LogLoginFailureTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\LogLoginFailure;
use App\Entity\User;
use App\Tests\Integration\TestCase\EntityTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;
use function array_key_exists;
use function ucfirst;

/**
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method LogLoginFailure getEntity()
 */
final class LogLoginFailureTest extends EntityTestCase
{
    /**
     * @var class-string
     */
    protected static string $entityName = LogLoginFailure::class;

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorksWithoutReadOnlyFlag')]
    #[TestDox('No setter for `$property` property in read only entity - so cannot test this')]
    #[Override]
    public function testThatSetterOnlyAcceptSpecifiedType(
        ?string $property = null,
        ?string $type = null,
        ?array $meta = null
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

        if ($type === 'boolean') {
            $getter = 'is' . ucfirst($property);
        }

        $logRequest = new LogLoginFailure(
            new User()
        );

        if (!(array_key_exists('columnName', $meta) || array_key_exists('joinColumns', $meta))) {
            $type = ArrayCollection::class;

            self::assertInstanceOf($type, $logRequest->{$getter}());
        }

        try {
            $method = 'assertIs' . ucfirst($type);

            self::$method($logRequest->{$getter}());
        } catch (Throwable $error) {
            /**
             * @var class-string $type
             */
            self::assertInstanceOf($type, $logRequest->{$getter}(), $error->getMessage());
        }
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Throwable
     */
    #[Override]
    protected function createEntity(): LogLoginFailure
    {
        return new LogLoginFailure(new User());
    }
}

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
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\FieldMapping;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Throwable;
use function ucfirst;

/**
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method LogLoginFailure getEntity()
 */
class LogLoginFailureTest extends EntityTestCase
{
    /**
     * @var class-string
     */
    protected static string $entityName = LogLoginFailure::class;

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorks')]
    #[TestDox('No setter for `$property` property in read only entity - so cannot test this')]
    #[Override]
    public function testThatSetterOnlyAcceptSpecifiedType(
        ?string $property = null,
        ?string $type = null,
        FieldMapping|AssociationMapping|null $meta = null
    ): void {
        self::markTestSkipped('There is not setter in read only entity...');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorks')]
    #[TestDox('No setter for `$property` property in read only entity - so cannot test this')]
    #[Override]
    public function testThatSetterReturnsInstanceOfEntity(
        ?string $property = null,
        ?string $type = null,
        FieldMapping|AssociationMapping|null $meta = null
    ): void {
        self::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorks')]
    #[TestDox('Test that getter method for `$type $property` property returns expected')]
    #[Override]
    public function testThatGetterReturnsExpectedValue(
        string $property,
        string $type,
        FieldMapping|AssociationMapping|null $meta,
    ): void {
        $getter = 'get' . ucfirst($property);

        if ($type === 'boolean') {
            $getter = 'is' . ucfirst($property);
        }

        $logRequest = new LogLoginFailure(new User());

        if ($meta instanceof AssociationMapping
            && (
                $meta->isManyToManyOwningSide()
                || $meta->isOneToMany()
            )
        ) {
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

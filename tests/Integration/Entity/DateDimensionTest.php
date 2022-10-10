<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/DateDimensionTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\DateDimension;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;

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

    public function testThatGetCreatedAtMethodReturnsExpected(): void
    {
        $entity = $this->createEntity();
        $createdAt = $entity->getCreatedAt();

        self::assertEqualsWithDelta(
            (new DateTime('now', new DateTimeZone('utc')))->getTimestamp(),
            $createdAt->getTimestamp(),
            1
        );
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function createEntity(): DateDimension
    {
        return new DateDimension(new DateTimeImmutable());
    }
}

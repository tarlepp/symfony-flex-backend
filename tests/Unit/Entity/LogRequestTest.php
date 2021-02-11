<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Entity/LogRequestTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Entity;

use App\Entity\LogRequest;
use App\Entity\User;
use DateTime;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * Class LogRequestTest
 *
 * @package App\Tests\Unit\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LogRequestTest extends KernelTestCase
{
    /**
     * @throws Throwable
     *
     * @testdox Test that `LogRequest::getCreatedAt` method returns expected
     */
    public function testThatGetCreatedAtReturnsExpected(): void
    {
        $entity = new LogRequest([]);

        static::assertEqualsWithDelta(new DateTime('now', new DateTimeZone('utc')), $entity->getCreatedAt(), 0.1);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `LogRequest::getUser` method returns `null` if user is not provided
     */
    public function testThatGetUserReturnsNullIfUserNotGiven(): void
    {
        $entity = new LogRequest([]);

        static::assertNull($entity->getUser());
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `LogRequest::getUser` method returns provided user
     */
    public function testThatGetUserReturnsExpectedUser(): void
    {
        $user = new User();
        $entity = new LogRequest([], null, null, $user);

        static::assertSame($user, $entity->getUser());
    }
}

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
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;

/**
 * @package App\Tests\Unit\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class LogRequestTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox('Test that `LogRequest::getCreatedAt` method returns expected')]
    public function testThatGetCreatedAtReturnsExpected(): void
    {
        $entity = new LogRequest([]);
        $createdAt = $entity->getCreatedAt();

        self::assertNotNull($createdAt);
        self::assertEqualsWithDelta(
            new DateTimeImmutable('now', new DateTimeZone('utc'))->getTimestamp(),
            $createdAt->getTimestamp(),
            1,
        );
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `LogRequest::getUser` method returns `null` if user is not provided')]
    public function testThatGetUserReturnsNullIfUserNotGiven(): void
    {
        $entity = new LogRequest([]);

        self::assertNull($entity->getUser());
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `LogRequest::getUser` method returns provided user')]
    public function testThatGetUserReturnsExpectedUser(): void
    {
        $user = new User();
        $entity = new LogRequest([], null, null, $user);

        self::assertSame($user, $entity->getUser());
    }
}

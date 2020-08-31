<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Entity/LogLoginTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\Entity;

use App\Doctrine\DBAL\Types\EnumLogLoginType;
use App\Entity\LogLogin;
use App\Entity\User;
use DateTime;
use DateTimeZone;
use DeviceDetector\DeviceDetector;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * Class LogLoginTest
 *
 * @package App\Tests\Unit\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLoginTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatGetCreatedAtReturnsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `getCreatedAt` method returns expected with `$type` type.
     */
    public function testThatGetCreatedAtReturnsExpected(
        string $type,
        Request $request,
        DeviceDetector $deviceDetector
    ): void {
        $entity = new LogLogin($type, $request, $deviceDetector);

        static::assertEqualsWithDelta(new DateTime('now', new DateTimeZone('utc')), $entity->getCreatedAt(), 0.1);
    }

    /**
     * @throws Throwable
     */
    public function testThatGetUserReturnsNullIfUserNotGiven(): void
    {
        $entity = new LogLogin(EnumLogLoginType::TYPE_SUCCESS, new Request(), new DeviceDetector(''));

        static::assertNull($entity->getUser());
    }

    /**
     * @throws Throwable
     */
    public function testThatGetUserReturnsExpectedUser(): void
    {
        $user = new User();
        $entity = new LogLogin(EnumLogLoginType::TYPE_SUCCESS, new Request(), new DeviceDetector(''), $user);

        static::assertSame($user, $entity->getUser());
    }

    public function dataProviderTestThatGetCreatedAtReturnsExpected(): Generator
    {
        yield [EnumLogLoginType::TYPE_SUCCESS, new Request(), new DeviceDetector('')];

        yield [EnumLogLoginType::TYPE_FAILURE, new Request(), new DeviceDetector('')];
    }
}

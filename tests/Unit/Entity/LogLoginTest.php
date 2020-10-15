<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Entity/LogLoginTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LogLoginTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatGetCreatedAtReturnsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `LogLogin::getCreatedAt` method returns expected with `$type` type
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
     * @dataProvider dataProviderTestThatGetUserReturnsNullIfUserNotGiven
     *
     * @throws Throwable
     *
     * @testdox Test that `LogLogin::getUser` method returns `null` if user is not provided with `$type` type
     */
    public function testThatGetUserReturnsNullIfUserNotGiven(
        string $type,
        Request $request,
        DeviceDetector $deviceDetector
    ): void {
        $entity = new LogLogin($type, $request, $deviceDetector);

        static::assertNull($entity->getUser());
    }

    /**
     * @dataProvider dataProviderTestThatGetUserReturnsExpectedUser
     *
     * @throws Throwable
     *
     * @testdox Test that `LogLogin::getUser` method returns provided user with `$type` type
     */
    public function testThatGetUserReturnsExpectedUser(
        string $type,
        Request $request,
        DeviceDetector $deviceDetector,
        User $user
    ): void {
        $entity = new LogLogin($type, $request, $deviceDetector, $user);

        static::assertSame($user, $entity->getUser());
    }

    public function dataProviderTestThatGetCreatedAtReturnsExpected(): Generator
    {
        yield [EnumLogLoginType::TYPE_SUCCESS, new Request(), new DeviceDetector('')];

        yield [EnumLogLoginType::TYPE_FAILURE, new Request(), new DeviceDetector('')];
    }

    public function dataProviderTestThatGetUserReturnsNullIfUserNotGiven(): Generator
    {
        yield [EnumLogLoginType::TYPE_SUCCESS, new Request(), new DeviceDetector('')];

        yield [EnumLogLoginType::TYPE_FAILURE, new Request(), new DeviceDetector('')];
    }

    public function dataProviderTestThatGetUserReturnsExpectedUser(): Generator
    {
        yield [EnumLogLoginType::TYPE_SUCCESS, new Request(), new DeviceDetector(''), new User()];

        yield [EnumLogLoginType::TYPE_FAILURE, new Request(), new DeviceDetector(''), new User()];
    }
}

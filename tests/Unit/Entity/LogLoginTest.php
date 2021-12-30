<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Entity/LogLoginTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Entity;

use App\Entity\LogLogin;
use App\Entity\User;
use App\Enum\Login;
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
        Login $type,
        Request $request,
        DeviceDetector $deviceDetector
    ): void {
        $entity = new LogLogin($type, $request, $deviceDetector);
        $createdAt = $entity->getCreatedAt();

        self::assertNotNull($createdAt);
        self::assertEqualsWithDelta(
            (new DateTime('now', new DateTimeZone('utc')))->getTimestamp(),
            $createdAt->getTimestamp(),
            1
        );
    }

    /**
     * @dataProvider dataProviderTestThatGetUserReturnsNullIfUserNotGiven
     *
     * @throws Throwable
     *
     * @testdox Test that `LogLogin::getUser` method returns `null` if user is not provided with `$type` type
     */
    public function testThatGetUserReturnsNullIfUserNotGiven(
        Login $type,
        Request $request,
        DeviceDetector $deviceDetector
    ): void {
        $entity = new LogLogin($type, $request, $deviceDetector);

        self::assertNull($entity->getUser());
    }

    /**
     * @dataProvider dataProviderTestThatGetUserReturnsExpectedUser
     *
     * @throws Throwable
     *
     * @testdox Test that `LogLogin::getUser` method returns provided user with `$type` type
     */
    public function testThatGetUserReturnsExpectedUser(
        Login $type,
        Request $request,
        DeviceDetector $deviceDetector,
        User $user
    ): void {
        $entity = new LogLogin($type, $request, $deviceDetector, $user);

        self::assertSame($user, $entity->getUser());
    }

    /**
     * @return Generator<array{0: Login, 1: Request, 2: DeviceDetector}>
     */
    public function dataProviderTestThatGetCreatedAtReturnsExpected(): Generator
    {
        yield [Login::SUCCESS, new Request(), new DeviceDetector('')];

        yield [Login::FAILURE, new Request(), new DeviceDetector('')];
    }

    /**
     * @return Generator<array{0: Login, 1: Request, 2: DeviceDetector}>
     */
    public function dataProviderTestThatGetUserReturnsNullIfUserNotGiven(): Generator
    {
        yield [Login::SUCCESS, new Request(), new DeviceDetector('')];

        yield [Login::FAILURE, new Request(), new DeviceDetector('')];
    }

    /**
     * @return Generator<array{0: Login, 1: Request, 2: DeviceDetector}>
     */
    public function dataProviderTestThatGetUserReturnsExpectedUser(): Generator
    {
        yield [Login::SUCCESS, new Request(), new DeviceDetector(''), new User()];

        yield [Login::FAILURE, new Request(), new DeviceDetector(''), new User()];
    }
}

<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Controller/v1/Auth/GetTokenControllerTest.php
 */

namespace App\Tests\Integration\Controller\v1\Auth;

use App\Controller\v1\Auth\GetTokenController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * @property GetTokenController $controller
 */
final class GetTokenControllerTest extends KernelTestCase
{
    public function testThatGetTokenThrowsAnException(): void
    {
        try {
            new GetTokenController()();
        } catch (Throwable $exception) {
            self::assertInstanceOf(HttpException::class, $exception);
            self::assertSame(
                'You need to send JSON body to obtain token eg. {"username":"username","password":"password"}',
                $exception->getMessage()
            );

            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            self::assertSame(400, $exception->getStatusCode());
        }
    }
}

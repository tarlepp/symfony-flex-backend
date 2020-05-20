<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/AuthControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Controller;

use App\Controller\AuthController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Class AuthControllerTest
 *
 * @package App\Tests\Integration\Controller
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @property AuthController $controller
 */
class AuthControllerTest extends KernelTestCase
{
    public function testThatGetTokenThrowsAnException(): void
    {
        try {
            $controller = new AuthController();
            $controller->getTokenAction();
        } catch (Throwable $exception) {
            static::assertInstanceOf(HttpException::class, $exception);
            static::assertSame(
                'You need to send JSON body to obtain token eg. {"username":"username","password":"password"}',
                $exception->getMessage()
            );

            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            static::assertSame(400, $exception->getStatusCode());
        }
    }
}

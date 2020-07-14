<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Controller/Auth/GetTokenControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Controller\Auth;

use App\Controller\Auth\GetTokenController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Class GetTokenControllerTest
 *
 * @package App\Tests\Integration\Controller\Auth
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @property GetTokenController $controller
 */
class GetTokenControllerTest extends KernelTestCase
{
    public function testThatGetTokenThrowsAnException(): void
    {
        try {
            (new GetTokenController())();
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

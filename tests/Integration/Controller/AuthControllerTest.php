<?php
declare(strict_types=1);
/**
 * /tests/Integration/Controller/AuthControllerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Controller;

use App\Controller\AuthController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class AuthControllerTest
 *
 * @package App\Tests\Integration\Controller
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class AuthControllerTest extends KernelTestCase
{
    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage You need to send JSON body to obtain token eg. {"username":"username","password":"password"}
     */
    public function testThatGetTokenThrowsAnException(): void
    {
        $controller = new AuthController();
        $controller->getTokenAction();
    }
}

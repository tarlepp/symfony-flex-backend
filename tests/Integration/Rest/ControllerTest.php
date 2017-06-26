<?php
declare(strict_types=1);
/**
 * /tests/Integration/Rest/ControllerTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Rest;

use App\Rest\Controller;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class ControllerTest
 *
 * @package App\Tests\Integration\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ControllerTest extends KernelTestCase
{
    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Resource service not set
     */
    public function testThatGetResourceThrowsAnExceptionIfNotSet(): void
    {
        /** @var Controller $controller */
        $controller = $this->getMockForAbstractClass(Controller::class);
        $controller->getResource();
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Response handler not set
     */
    public function testThatGetResponseHandlerThrowsAnExceptionIfNotSet(): void
    {
        /** @var Controller $controller */
        $controller = $this->getMockForAbstractClass(Controller::class);
        $controller->getResponseHandler();
    }
}

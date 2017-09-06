<?php
declare(strict_types=1);
/**
 * /src/Utils/Tests/RestIntegrationControllerTestCase.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Utils\Tests;

use App\Rest\ControllerInterface;
use App\Rest\ResponseHandlerInterface;

/**
 * Class RestIntegrationControllerTestCase
 *
 * @package App\Utils\Tests
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RestIntegrationControllerTestCase extends ContainerTestCase
{
    /**
     * @var ControllerInterface
     */
    protected $controller;

    /**
     * @var string
     */
    protected $controllerClass;

    /**
     * @var string
     */
    protected $resourceClass;

    public function setUp(): void
    {
        parent::setUp();

        $this->controller = $this->getContainer()->get($this->controllerClass);
    }

    public function testThatGivenControllerIsCorrect(): void
    {
        $expected = \mb_substr((new \ReflectionClass($this))->getShortName(), 0, -4);

        $message = \sprintf(
            'Your REST controller integration test \'%s\' uses likely wrong controller class \'%s\'',
            \get_class($this),
            $this->controllerClass
        );

        static::assertSame($expected, (new \ReflectionClass($this->controller))->getShortName(), $message);
    }

    public function testThatGetResourceReturnsExpected(): void
    {
        static::assertInstanceOf($this->resourceClass, $this->controller->getResource());
    }

    public function testThatGetResponseHandlerReturnsExpected(): void
    {
        static::assertInstanceOf(ResponseHandlerInterface::class, $this->controller->getResponseHandler());
    }
}

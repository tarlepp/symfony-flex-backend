<?php
declare(strict_types = 1);
/**
 * /src/Utils/Tests/RestIntegrationControllerTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Utils\Tests;

use App\Rest\Controller;
use ReflectionClass;
use UnexpectedValueException;
use function gc_collect_cycles;
use function gc_enable;
use function mb_substr;
use function sprintf;

/**
 * Class RestIntegrationControllerTestCase
 *
 * @package App\Utils\Tests
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
abstract class RestIntegrationControllerTestCase extends ContainerTestCase
{
    protected ?Controller $controller = null;

    /**
     * @var class-string
     */
    protected string $controllerClass;

    /**
     * @var class-string
     */
    protected string $resourceClass;

    protected function setUp(): void
    {
        gc_enable();

        parent::setUp();

        /** @var Controller $controller */
        $controller = $this->getContainer()->get($this->controllerClass);

        $this->controller = $controller;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->controller);

        gc_collect_cycles();
    }

    public function testThatGivenControllerIsCorrect(): void
    {
        $expected = mb_substr((new ReflectionClass($this))->getShortName(), 0, -4);

        $message = sprintf(
            'Your REST controller integration test \'%s\' uses likely wrong controller class \'%s\'',
            static::class,
            $this->controllerClass
        );

        static::assertSame($expected, (new ReflectionClass($this->getController()))->getShortName(), $message);
    }

    /**
     * This test is to make sure that controller has set the expected resource.
     * There is multiple resources and each controller needs to use specified
     * one.
     */
    public function testThatGetResourceReturnsExpected(): void
    {
        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf($this->resourceClass, $this->getController()->getResource());
    }

    protected function getController(): Controller
    {
        return $this->controller instanceof Controller
            ? $this->controller
            : throw new UnexpectedValueException('Controller not set');
    }
}

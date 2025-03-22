<?php
declare(strict_types = 1);
/**
 * /tests/Integration/TestCase/RestIntegrationControllerTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\TestCase;

use App\Rest\Controller;
use Override;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use UnexpectedValueException;
use function assert;
use function mb_substr;
use function sprintf;

/**
 * @package App\Tests\Integration\TestCase
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
abstract class RestIntegrationControllerTestCase extends KernelTestCase
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

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $controller = static::getContainer()->get($this->controllerClass);

        assert($controller instanceof Controller);

        $this->controller = $controller;
    }

    public function testThatGivenControllerIsCorrect(): void
    {
        $expected = mb_substr((new ReflectionClass($this))->getShortName(), 0, -4);

        $message = sprintf(
            'Your REST controller integration test \'%s\' uses likely wrong controller class \'%s\'',
            static::class,
            $this->controllerClass
        );

        self::assertSame($expected, (new ReflectionClass($this->getController()))->getShortName(), $message);
    }

    /**
     * This test is to make sure that controller has set the expected resource.
     * There is multiple resources and each controller needs to use specified
     * one.
     */
    public function testThatGetResourceReturnsExpected(): void
    {
        /** @noinspection UnnecessaryAssertionInspection */
        self::assertInstanceOf($this->resourceClass, $this->getController()->getResource());
    }

    protected function getController(): Controller
    {
        return $this->controller instanceof Controller
            ? $this->controller
            : throw new UnexpectedValueException('Controller not set');
    }
}

<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/ControllerCollectionTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Rest;

use App\Controller\ApiKeyController;
use App\Controller\AuthController;
use App\Controller\RoleController;
use App\Controller\UserController;
use App\Controller\UserGroupController;
use App\Rest\ControllerCollection;
use ArrayObject;
use Generator;
use InvalidArgumentException;
use IteratorAggregate;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class ControllerCollectionTest
 *
 * @package App\Tests\Integration\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ControllerCollectionTest extends KernelTestCase
{
    public function testThatGetMethodThrowsAnException(): void
    {
        $stubLogger = $this->createMock(LoggerInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('REST controller \'FooBar\' does not exists');

        $iteratorAggregate = new class([]) implements IteratorAggregate {
            private ArrayObject $iterator;

            /**
             * Constructor  of the class.
             *
             * @param $input
             */
            public function __construct($input)
            {
                $this->iterator = new ArrayObject($input);
            }

            /**
             * {@inheritdoc}
             */
            public function getIterator(): ArrayObject
            {
                return $this->iterator;
            }
        };

        $collection = new ControllerCollection($iteratorAggregate, $stubLogger);
        $collection->get('FooBar');
    }

    public function testThatGetAllReturnsCorrectCountOfRestControllers(): void
    {
        $collection = $this->getCollection();

        static::assertCount(11, $collection->getAll());
    }

    /**
     * @dataProvider dataProviderTestThatGetReturnsExpectedController
     *
     * @param string $controllerName
     *
     * @testdox Test that `get` method with `$controllerName` input returns instance of that controller.
     */
    public function testThatGetReturnsExpectedController(string $controllerName): void
    {
        $collection = $this->getCollection();

        static::assertInstanceOf($controllerName, $collection->get($controllerName));
    }

    /**
     * @dataProvider dataProviderTestThatHasReturnsExpected
     *
     * @param bool        $expected
     * @param string|null $controller
     *
     * @testdox Test that `has` method returns `$expected` with `$controller` input.
     */
    public function testThatHasReturnsExpected(bool $expected, ?string $controller): void
    {
        $collection = $this->getCollection();

        static::assertSame($expected, $collection->has($controller));
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatGetReturnsExpectedController(): Generator
    {
        yield [ApiKeyController::class];
        yield [RoleController::class];
        yield [UserController::class];
        yield [UserGroupController::class];
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatHasReturnsExpected(): Generator
    {
        yield [true, ApiKeyController::class];
        yield [true, RoleController::class];
        yield [true, UserController::class];
        yield [true, UserGroupController::class];
        yield [false, null];
        yield [false, 'foobar'];
        yield [false, AuthController::class];
    }

    /**
     * @return ControllerCollection
     */
    private function getCollection(): ControllerCollection
    {
        static::bootKernel();

        return static::$container->get(ControllerCollection::class);
    }
}

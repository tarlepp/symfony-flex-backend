<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/ControllerCollectionTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest;

use App\Controller\ApiKeyController;
use App\Controller\Auth\GetTokenController;
use App\Controller\Role\FindOneRoleController;
use App\Controller\Role\RoleController;
use App\Controller\User\DeleteUserController;
use App\Controller\User\UserController;
use App\Controller\UserGroup\UserGroupController;
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ControllerCollectionTest extends KernelTestCase
{
    public function testThatGetMethodThrowsAnException(): void
    {
        $stubLogger = $this->createMock(LoggerInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('REST controller \'FooBar\' does not exist');

        $iteratorAggregate = new class([]) implements IteratorAggregate {
            private ArrayObject $iterator;

            /**
             * Constructor of the class.
             *
             * @param $input
             */
            public function __construct($input)
            {
                $this->iterator = new ArrayObject($input);
            }

            public function getIterator(): ArrayObject
            {
                return $this->iterator;
            }
        };

        (new ControllerCollection($iteratorAggregate, $stubLogger))->get('FooBar');
    }

    public function testThatGetAllReturnsCorrectCountOfRestControllers(): void
    {
        $collection = $this->getCollection();

        static::assertCount(13, $collection->getAll());
    }

    /**
     * @dataProvider dataProviderTestThatGetReturnsExpectedController
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
     * @testdox Test that `has` method returns `$expected` with `$controller` input.
     */
    public function testThatHasReturnsExpected(bool $expected, ?string $controller): void
    {
        $collection = $this->getCollection();

        static::assertSame($expected, $collection->has($controller));
    }

    public function dataProviderTestThatGetReturnsExpectedController(): Generator
    {
        yield [ApiKeyController::class];
        yield [RoleController::class];
        yield [FindOneRoleController::class];
        yield [UserController::class];
        yield [UserGroupController::class];
        yield [DeleteUserController::class];
    }

    public function dataProviderTestThatHasReturnsExpected(): Generator
    {
        yield [true, ApiKeyController::class];
        yield [true, RoleController::class];
        yield [true, FindOneRoleController::class];
        yield [true, UserController::class];
        yield [true, UserGroupController::class];
        yield [true, DeleteUserController::class];
        yield [false, null];
        yield [false, 'foobar'];
        yield [false, GetTokenController::class];
    }

    private function getCollection(): ControllerCollection
    {
        static::bootKernel();

        return static::$container->get(ControllerCollection::class);
    }
}

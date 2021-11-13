<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/ControllerCollectionTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest;

use App\Controller\v1\ApiKey\ApiKeyController;
use App\Controller\v1\Auth\GetTokenController;
use App\Controller\v1\Role\FindOneRoleController;
use App\Controller\v1\Role\RoleController;
use App\Controller\v1\User\DeleteUserController;
use App\Controller\v1\User\UserController;
use App\Controller\v1\UserGroup\UserGroupController;
use App\Rest\Controller;
use App\Rest\ControllerCollection;
use App\Rest\Interfaces\ControllerInterface;
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
    /**
     * @testdox Test that `get` method throws an exception when specified `REST` controller is not found
     */
    public function testThatGetMethodThrowsAnException(): void
    {
        $stubLogger = $this->createMock(LoggerInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('REST controller \'FooBar\' does not exist');

        $iteratorAggregate = new class([]) implements IteratorAggregate {
            /**
             * @phpstan-var ArrayObject<int, mixed>
             */
            private ArrayObject $iterator;

            /**
             * Constructor of the class.
             *
             * @param array<mixed> $input
             */
            public function __construct(array $input)
            {
                $this->iterator = new ArrayObject($input);
            }

            /**
             * @phpstan-return ArrayObject<int, mixed>
             */
            public function getIterator(): ArrayObject
            {
                return $this->iterator;
            }
        };

        (new ControllerCollection($iteratorAggregate, $stubLogger))->get('FooBar');
    }

    /**
     * @testdox Test that `getAll` method returns expected count of `REST` controllers
     */
    public function testThatGetAllReturnsCorrectCountOfRestControllers(): void
    {
        $collection = $this->getCollection();

        self::assertCount(13, $collection->getAll());
    }

    /**
     * @dataProvider dataProviderTestThatGetReturnsExpectedController
     *
     * @param class-string<Controller> $controllerName
     *
     * @testdox Test that `get` method with `$controllerName` input returns instance of that controller
     */
    public function testThatGetReturnsExpectedController(string $controllerName): void
    {
        $collection = $this->getCollection();

        self::assertInstanceOf($controllerName, $collection->get($controllerName));
    }

    /**
     * @dataProvider dataProviderTestThatHasReturnsExpected
     *
     * @param class-string<Controller>|string|null $controller
     *
     * @testdox Test that `has` method returns `$expected` with `$controller` input
     */
    public function testThatHasReturnsExpected(bool $expected, ?string $controller): void
    {
        $collection = $this->getCollection();

        self::assertSame($expected, $collection->has($controller));
    }

    /**
     * @return Generator<array{0: class-string<Controller>}>
     */
    public function dataProviderTestThatGetReturnsExpectedController(): Generator
    {
        yield [ApiKeyController::class];
        yield [RoleController::class];
        yield [FindOneRoleController::class];
        yield [UserController::class];
        yield [UserGroupController::class];
        yield [DeleteUserController::class];
    }

    /**
     * @return Generator<array{0: boolean, 1: class-string<Controller>|string|null}>
     */
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

    /**
     * @return ControllerCollection<ControllerInterface>
     */
    private function getCollection(): ControllerCollection
    {
        return self::getContainer()->get(ControllerCollection::class);
    }
}

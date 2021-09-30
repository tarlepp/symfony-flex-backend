<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/DeleteMethodTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods;

use App\Entity\Interfaces\EntityInterface;
use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\Rest\Traits\Methods\src\DeleteMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\DeleteMethodTestClass;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Generator;
use InvalidArgumentException;
use LogicException;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Class DeleteMethodTest
 *
 * @package Integration\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class DeleteMethodTest extends KernelTestCase
{
    /**
     * @throws Throwable
     *
     * @testdox Test that `deleteMethod` throws an exception if class doesn't implement `ControllerInterface`
     */
    public function testThatTraitThrowsAnException(): void
    {
        $inValidTestClassMock = $this->getMockForAbstractClass(DeleteMethodInvalidTestClass::class);

        $this->expectException(LogicException::class);

        /* @codingStandardsIgnoreStart */
        $this->expectExceptionMessageMatches(
            '/You cannot use (.*) controller class with REST traits if that does not implement (.*)ControllerInterface\'/'
        );
        /* @codingStandardsIgnoreEnd */

        $inValidTestClassMock->deleteMethod(
            Request::create('/' . Uuid::uuid4()->toString(), 'DELETE'),
            'some-id'
        );
    }

    /**
     * @dataProvider dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod
     *
     * @throws Throwable
     *
     * @testdox Test that `deleteMethod` throws an exception when using `$httpMethod` HTTP method
     */
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $validTestClassMock = $this->getMockForAbstractClass(
            DeleteMethodTestClass::class,
            [$resourceMock, $responseHandlerMock]
        );

        $this->expectException(MethodNotAllowedHttpException::class);

        $validTestClassMock->deleteMethod(
            Request::create('/' . Uuid::uuid4()->toString(), $httpMethod),
            'some-id'
        );
    }

    /**
     * @dataProvider dataProviderTestThatTraitHandlesException
     *
     * @throws Throwable
     *
     * @testdox Test that `deleteMethod` uses `$expectedCode` HTTP status code with `$exception` exception
     */
    public function testThatTraitHandlesException(Throwable $exception, int $expectedCode): void
    {
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $validTestClassMock = $this->getMockForAbstractClass(
            DeleteMethodTestClass::class,
            [$resourceMock, $responseHandlerMock]
        );

        $uuid = Uuid::uuid4()->toString();
        $request = Request::create('/' . $uuid, 'DELETE');

        $resourceMock
            ->expects(static::once())
            ->method('delete')
            ->with($uuid)
            ->willThrowException($exception);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $validTestClassMock->deleteMethod($request, $uuid);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `deleteMethod` method calls expected service methods
     */
    public function testThatTraitCallsServiceMethods(): void
    {
        $entityMock = $this->getMockBuilder(EntityInterface::class)->getMock();
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $validTestClassMock = $this->getMockForAbstractClass(
            DeleteMethodTestClass::class,
            [$resourceMock, $responseHandlerMock]
        );

        $uuid = Uuid::uuid4()->toString();
        $request = Request::create('/' . $uuid, 'DELETE');

        $resourceMock
            ->expects(static::once())
            ->method('delete')
            ->with($uuid)
            ->willReturn($entityMock);

        $responseHandlerMock
            ->expects(static::once())
            ->method('createResponse')
            ->with($request, $entityMock, $resourceMock);

        $validTestClassMock->deleteMethod($request, $uuid);
    }

    /**
     * @return Generator<array{0: string}>
     */
    public function dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod(): Generator
    {
        yield ['HEAD'];
        yield ['GET'];
        yield ['PATCH'];
        yield ['PUT'];
        yield ['POST'];
        yield ['OPTIONS'];
        yield ['CONNECT'];
        yield ['foobar'];
    }

    /**
     * @return Generator<array{0: Throwable, 1: int}>
     */
    public function dataProviderTestThatTraitHandlesException(): Generator
    {
        yield [new HttpException(400, '', null, [], 400), 400];
        yield [new NoResultException(), 404];
        yield [new NotFoundHttpException(), 404];
        yield [new NonUniqueResultException(), 500];
        yield [new Exception(), 400];
        yield [new LogicException(), 400];
        yield [new InvalidArgumentException(), 400];
    }
}

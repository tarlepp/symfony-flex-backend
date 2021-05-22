<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/FindOneMethodTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods;

use App\Entity\Interfaces\EntityInterface;
use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\Rest\Traits\Methods\src\FindOneMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\FindOneMethodTestClass;
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
 * Class FindOneMethodTest
 *
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class FindOneMethodTest extends KernelTestCase
{
    /**
     * @throws Throwable
     *
     * @testdox Test that `findOneMethod` throws an exception if class doesn't implement `ControllerInterface`
     */
    public function testThatTraitThrowsAnException(): void
    {
        $this->expectException(LogicException::class);

        /* @codingStandardsIgnoreStart */
        $this->expectExceptionMessageMatches(
            '/You cannot use (.*) controller class with REST traits if that does not implement (.*)ControllerInterface\'/'
        );
        /* @codingStandardsIgnoreEnd */

        $this->getMockForAbstractClass(FindOneMethodInvalidTestClass::class)
            ->findOneMethod(Request::create('/' . Uuid::uuid4()->toString()), 'some-id');
    }

    /**
     * @dataProvider dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod
     *
     * @throws Throwable
     *
     * @testdox Test that `findOneMethod` throws an exception when using `$httpMethod` HTTP method
     */
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        [, , , $testClassMock] = $this->getMocks();

        $this->expectException(MethodNotAllowedHttpException::class);

        $testClassMock
            ->findOneMethod(Request::create('/' . Uuid::uuid4()->toString(), $httpMethod), 'some-id')
            ->getContent();
    }

    /**
     * @dataProvider dataProviderTestThatTraitHandlesException
     *
     * @throws Throwable
     *
     * @testdox Test that `findOneMethod` uses `$expectedCode` HTTP status code with `$exception` exception
     */
    public function testThatTraitHandlesException(Throwable $exception, int $expectedCode): void
    {
        $uuid = Uuid::uuid4()->toString();

        [, $restResourceMock, , $testClassMock] = $this->getMocks();

        $restResourceMock
            ->expects(static::once())
            ->method('findOne')
            ->with($uuid)
            ->willThrowException($exception);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $testClassMock->findOneMethod(Request::create('/' . $uuid), $uuid);
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `findOneMethod` method calls expected service methods
     */
    public function testThatTraitCallsServiceMethods(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $request = Request::create('/' . $uuid);

        [$entityMock, $restResourceMock, $responseHandlerMock, $testClassMock] = $this->getMocks();

        $restResourceMock
            ->expects(static::once())
            ->method('findOne')
            ->with($uuid, true)
            ->willReturn($entityMock);

        $responseHandlerMock
            ->expects(static::once())
            ->method('createResponse')
            ->with($request, $entityMock, $restResourceMock);

        $testClassMock->findOneMethod($request, $uuid);
    }

    /**
     * @return Generator<array{0: string}>
     */
    public function dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod(): Generator
    {
        yield ['HEAD'];
        yield ['DELETE'];
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

    /**
     * @return array{
     *      0: \PHPUnit\Framework\MockObject\MockObject&EntityInterface,
     *      1: \PHPUnit\Framework\MockObject\MockObject&RestResourceInterface,
     *      2: \PHPUnit\Framework\MockObject\MockObject&ResponseHandlerInterface,
     *      3: \PHPUnit\Framework\MockObject\MockObject&FindOneMethodTestClass,
     *  }
     */
    private function getMocks(): array
    {
        $entityMock = $this->getMockBuilder(EntityInterface::class)->getMock();
        $restResourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $testClassMock = $this->getMockForAbstractClass(
            FindOneMethodTestClass::class,
            [$restResourceMock, $responseHandlerMock],
        );

        return [$entityMock, $restResourceMock, $responseHandlerMock, $testClassMock];
    }
}

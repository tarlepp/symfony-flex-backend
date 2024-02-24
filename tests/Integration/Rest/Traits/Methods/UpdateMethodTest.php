<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/UpdateMethodTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods;

use App\DTO\RestDtoInterface;
use App\Entity\Interfaces\EntityInterface;
use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Tests\Integration\Rest\Traits\Methods\src\UpdateMethodInvalidTestClass;
use App\Tests\Integration\Rest\Traits\Methods\src\UpdateMethodTestClass;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use Generator;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * @package App\Tests\Integration\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UpdateMethodTest extends KernelTestCase
{
    /**
     * @throws Throwable
     */
    #[TestDox("Test that `updateMethod` throws an exception if class doesn't implement `ControllerInterface`")]
    public function testThatTraitThrowsAnException(): void
    {
        $restDtoMock = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $inValidTestClassMock = $this->getMockForAbstractClass(UpdateMethodInvalidTestClass::class);

        $this->expectException(LogicException::class);

        $regex = '/You cannot use (.*) controller class with REST traits if that does not implement ' .
            '(.*)ControllerInterface\'/';

        $this->expectExceptionMessageMatches($regex);

        $request = Request::create('/' . Uuid::uuid4()->toString(), 'PUT');

        $inValidTestClassMock->updateMethod($request, $restDtoMock, 'some-id');
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod')]
    #[TestDox('Test that `updateMethod` throws an exception when using `$httpMethod` HTTP method')]
    public function testThatTraitThrowsAnExceptionWithWrongHttpMethod(string $httpMethod): void
    {
        $restDtoMock = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $validTestClassMock = $this->getMockForAbstractClass(
            UpdateMethodTestClass::class,
            [$resourceMock, $responseHandlerMock]
        );

        $this->expectException(MethodNotAllowedHttpException::class);

        $request = Request::create('/' . Uuid::uuid4()->toString(), $httpMethod);

        $validTestClassMock->updateMethod($request, $restDtoMock, 'some-id')->getContent();
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatTraitHandlesException')]
    #[TestDox('Test that `updateMethod` uses `$expectedCode` HTTP status code with `$exception` exception')]
    public function testThatTraitHandlesException(Throwable $exception, int $expectedCode): void
    {
        $restDtoMock = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $validTestClassMock = $this->getMockForAbstractClass(
            UpdateMethodTestClass::class,
            [$resourceMock, $responseHandlerMock]
        );

        $uuid = Uuid::uuid4()->toString();
        $request = Request::create('/' . $uuid, 'PUT');

        $resourceMock
            ->expects(self::once())
            ->method('update')
            ->with($uuid, $restDtoMock, true)
            ->willThrowException($exception);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode($expectedCode);

        $validTestClassMock->updateMethod($request, $restDtoMock, $uuid);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `updateMethod` method calls expected service methods')]
    public function testThatTraitCallsServiceMethods(): void
    {
        $restDtoMock = $this->getMockBuilder(RestDtoInterface::class)->getMock();
        $responseHandlerMock = $this->getMockBuilder(ResponseHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resourceMock = $this->getMockBuilder(RestResourceInterface::class)->getMock();
        $entityMock = $this->getMockBuilder(EntityInterface::class)->getMock();
        $validTestClassMock = $this->getMockForAbstractClass(
            UpdateMethodTestClass::class,
            [$resourceMock, $responseHandlerMock]
        );

        $uuid = Uuid::uuid4()->toString();

        $request = Request::create('/' . $uuid, 'PUT');

        $resourceMock
            ->expects(self::once())
            ->method('update')
            ->with($uuid, $restDtoMock, true)
            ->willReturn($entityMock);

        $responseHandlerMock
            ->expects(self::once())
            ->method('createResponse')
            ->with($request, $entityMock, $resourceMock);

        $validTestClassMock->updateMethod($request, $restDtoMock, $uuid);
    }

    /**
     * @return Generator<array{0: string}>
     */
    public static function dataProviderTestThatTraitThrowsAnExceptionWithWrongHttpMethod(): Generator
    {
        yield ['HEAD'];
        yield ['DELETE'];
        yield ['GET'];
        yield ['PATCH'];
        yield ['POST'];
        yield ['OPTIONS'];
        yield ['CONNECT'];
        yield ['foobar'];
    }

    /**
     * @return Generator<array{0: Throwable, 1: int}>
     */
    public static function dataProviderTestThatTraitHandlesException(): Generator
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

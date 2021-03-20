<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/ResponseHandlerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Rest;

use App\Rest\Interfaces\RestResourceInterface;
use App\Rest\ResponseHandler;
use App\Utils\Tests\ContainerTestCase;
use Exception;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

/**
 * Class ResponseHandlerTest
 *
 * @package App\Tests\Integration\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ResponseHandlerTest extends ContainerTestCase
{
    public function testThatGetSerializerReturnsExpected(): void
    {
        /** @var SerializerInterface $serializer */
        $serializer = $this->getContainer()->get(SerializerInterface::class);

        $responseClass = new ResponseHandler($serializer);

        static::assertSame($serializer, $responseClass->getSerializer());
    }

    /**
     * @dataProvider dataProviderTestThatCreateResponseReturnsExpected
     *
     * @param array<string, string> $data
     *
     * @throws Throwable
     *
     * @testdox Test that response is `$expectedContent` when using `$request` request with `$data` data.
     */
    public function testThatCreateResponseReturnsExpected(
        Request $request,
        array $data,
        string $expectedContent
    ): void {
        /** @var SerializerInterface $serializer */
        $serializer = $this->getContainer()->get(SerializerInterface::class);

        $stubResourceService = $this->createMock(RestResourceInterface::class);

        $httpResponse = (new ResponseHandler($serializer))->createResponse($request, $data, $stubResourceService, 200);

        static::assertSame($expectedContent, $httpResponse->getContent());
    }

    /**
     * @throws Throwable
     */
    public function testThatCreateResponseThrowsAnExceptionIfSerializationFails(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Some exception');

        /** @var MockObject $stubSerializer */
        $stubSerializer = $this->createMock(SerializerInterface::class);

        $request = Request::create('');
        $exception = new Exception('Some exception');

        $stubSerializer
            ->expects(static::once())
            ->method('serialize')
            ->withAnyParameters()
            ->willThrowException($exception);

        /** @var SerializerInterface $stubSerializer */
        (new ResponseHandler($stubSerializer))
            ->createResponse($request, []);
    }

    /**
     * @dataProvider dataProviderTestThatNonSupportedSerializerFormatThrowsHttpException
     *
     * @throws Throwable
     *
     * @testdox Test that non supported serializer format `$format` throws an exception.
     */
    public function testThatNonSupportedSerializerFormatThrowsHttpException(string $format): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessageMatches('/Serialization for the format .* is not supported/');

        $request = Request::create('', 'GET', [], [], [], ['CONTENT_TYPE' => $format]);

        /** @var SerializerInterface $serializer */
        $serializer = $this->getContainer()->get(SerializerInterface::class);

        /** @var RestResourceInterface $stubResourceService */
        $stubResourceService = $this->createMock(RestResourceInterface::class);

        (new ResponseHandler($serializer))
            ->createResponse($request, ['foo' => 'bar'], $stubResourceService, 200, $format);
    }

    /**
     * @throws Throwable
     */
    public function testThatGetSerializeContextMethodCallsExpectedServiceMethods(): void
    {
        /** @var SerializerInterface $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var MockObject $parameterBag */
        $parameterBag = $this->createMock(ParameterBag::class);

        /** @var MockObject $restResource */
        $restResource = $this->createMock(RestResourceInterface::class);

        $parameterBag
            ->expects(static::exactly(2))
            ->method('all')
            ->willReturn([]);

        $restResource
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn('FakeEntity');

        /** @var InputBag $parameterBag */
        $request->query = $parameterBag;

        /** @var RestResourceInterface $restResource */
        $context = (new ResponseHandler($serializer))
            ->getSerializeContext($request, $restResource);

        static::assertSame(['FakeEntity'], $context['groups']);
    }

    /**
     * @throws Throwable
     */
    public function testThatGetSerializeContextSetExpectedGroupsWithPopulateAllParameterWhenNonAnyAssociations(): void
    {
        /** @var SerializerInterface $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var MockObject $parameterBag */
        $parameterBag = $this->createMock(ParameterBag::class);

        /** @var MockObject $restResource */
        $restResource = $this->createMock(RestResourceInterface::class);

        $parameterBag
            ->expects(static::exactly(2))
            ->method('all')
            ->willReturn(['populateAll' => '']);

        $restResource
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn('FakeEntity');

        /** @var InputBag $parameterBag */
        $request->query = $parameterBag;

        /** @var RestResourceInterface $restResource */
        $context = (new ResponseHandler($serializer))
            ->getSerializeContext($request, $restResource);

        static::assertSame(['FakeEntity'], $context['groups']);
    }

    /**
     * @throws Throwable
     */
    public function testThatGetSerializeContextSetExpectedGroupsWithPopulateAllParameterWhenAssociations(): void
    {
        /** @var SerializerInterface $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var MockObject $parameterBag */
        $parameterBag = $this->createMock(ParameterBag::class);

        /** @var MockObject $restResource */
        $restResource = $this->createMock(RestResourceInterface::class);

        $parameterBag
            ->expects(static::exactly(2))
            ->method('all')
            ->willReturn(['populateAll' => '']);

        $restResource
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn('FakeEntity');

        $restResource
            ->expects(static::once())
            ->method('getAssociations')
            ->willReturn(['AnotherFakeEntity']);

        /** @var InputBag $parameterBag */
        $request->query = $parameterBag;

        /** @var RestResourceInterface $restResource */
        $context = (new ResponseHandler($serializer))
            ->getSerializeContext($request, $restResource);

        static::assertSame(['FakeEntity', 'FakeEntity.AnotherFakeEntity'], $context['groups']);
    }

    /**
     * @throws Throwable
     */
    public function testThatGetSerializeContextSetExpectedGroupsWithPopulateOnlyParameterWhenNonAssociations(): void
    {
        /** @var SerializerInterface $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        /** @var Request $request */
        $request = $this->createMock(Request::class);

        /** @var MockObject $parameterBag */
        $parameterBag = $this->createMock(ParameterBag::class);

        /** @var MockObject $restResource */
        $restResource = $this->createMock(RestResourceInterface::class);

        $parameterBag
            ->expects(static::exactly(2))
            ->method('all')
            ->willReturn(['populateOnly' => '']);

        $restResource
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn('FakeEntity');

        /** @var InputBag $parameterBag */
        $request->query = $parameterBag;

        /** @var RestResourceInterface $restResource */
        $context = (new ResponseHandler($serializer))
            ->getSerializeContext($request, $restResource);

        static::assertSame(['FakeEntity'], $context['groups']);
    }

    /**
     * @throws Throwable
     */
    public function testThatGetSerializeContextSetExpectedGroupsWithPopulateOnlyParameterWhenEntityAssociations(): void
    {
        /** @var SerializerInterface $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        /** @var MockObject $request */
        $request = $this->createMock(Request::class);

        /** @var MockObject $parameterBag */
        $parameterBag = $this->createMock(ParameterBag::class);

        /** @var MockObject $restResource */
        $restResource = $this->createMock(RestResourceInterface::class);

        $parameterBag
            ->expects(static::exactly(2))
            ->method('all')
            ->willReturn(['populateOnly' => '']);

        $restResource
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn('FakeEntity');

        $request
            ->expects(static::once())
            ->method('get')
            ->with('populate')
            ->willReturn(['AnotherFakeEntity']);

        /**
         * @var Request  $request
         * @var InputBag $parameterBag
         */
        $request->query = $parameterBag;

        /** @var RestResourceInterface $restResource */
        $context = (new ResponseHandler($serializer))
            ->getSerializeContext($request, $restResource);

        static::assertSame(['AnotherFakeEntity'], $context['groups']);
    }

    /**
     * @throws Throwable
     */
    public function testThatGetSerializeContextReturnsExpectedWhenResourceHasGetSerializerContextMethod(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $request = $this->createMock(Request::class);
        $parameterBag = $this->createMock(ParameterBag::class);
        $restResource = $this->createMock(RestResourceInterface::class);

        $expected = [
            'groups' => 'foo',
            'some' => 'bar',
            'another' => 'foobar',
        ];

        $parameterBag
            ->expects(static::exactly(2))
            ->method('all')
            ->willReturn(['populateOnly' => '']);

        $restResource
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn('FakeEntity');

        $restResource
            ->expects(static::once())
            ->method('getSerializerContext')
            ->willReturn($expected);

        $request
            ->expects(static::once())
            ->method('get')
            ->with('populate')
            ->willReturn(['AnotherFakeEntity']);

        /** @var InputBag $parameterBag */
        $request->query = $parameterBag;

        /** @var RestResourceInterface $restResource */
        static::assertSame(
            $expected,
            (new ResponseHandler($serializer))->getSerializeContext($request, $restResource)
        );
    }

    /**
     * @throws Throwable
     */
    public function testThatHandleFormErrorThrowsExpectedExceptionWithProperty(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Field \'foo\': test error');

        /** @var SerializerInterface $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        $formInterface = $this->getMockBuilder(FormInterface::class)->getMock();
        $formError = $this->createMock(FormError::class);

        /**
         * Create FormErrorIterator
         *
         * @var FormInterface $formInterface
         * @var FormError $formError
         */
        $formErrorIterator = new FormErrorIterator($formInterface, [$formError]);

        /** @var MockObject $formInterface */
        $formInterface
            ->expects(static::once())
            ->method('getErrors')
            ->withAnyParameters()
            ->willReturn($formErrorIterator);

        $formInterface
            ->expects(static::once())
            ->method('getName')
            ->willReturn('foo');

        /** @var MockObject $formError */
        $formError
            ->expects(static::once())
            ->method('getOrigin')
            ->willReturn($formInterface);

        $formError
            ->expects(static::atLeast(1))
            ->method('getMessage')
            ->willReturn('test error');

        /** @var FormInterface $formInterface */
        (new ResponseHandler($serializer))->handleFormError($formInterface);
    }

    /**
     * @throws Throwable
     */
    public function testThatHandleFormErrorThrowsExpectedExceptionWithoutProperty(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('test error');

        /** @var SerializerInterface $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        $formInterface = $this->getMockBuilder(FormInterface::class)->getMock();
        $formError = $this->createMock(FormError::class);

        // Create FormErrorIterator
        $formErrorIterator = new FormErrorIterator($formInterface, [$formError]);

        $formInterface
            ->expects(static::once())
            ->method('getErrors')
            ->withAnyParameters()
            ->willReturn($formErrorIterator);

        $formInterface
            ->expects(static::once())
            ->method('getName')
            ->willReturn('');

        $formError
            ->expects(static::once())
            ->method('getOrigin')
            ->willReturn($formInterface);

        $formError
            ->expects(static::atLeast(1))
            ->method('getMessage')
            ->willReturn('test error');

        (new ResponseHandler($serializer))->handleFormError($formInterface);
    }

    /**
     * @return Generator<array{0: Request, 1: array<string, string>, 2: string}>
     */
    public function dataProviderTestThatCreateResponseReturnsExpected(): Generator
    {
        yield [
            Request::create(''),
            ['foo' => 'bar'],
            '{"foo":"bar"}',
        ];

        yield [
            Request::create('', 'GET', [], [], [], ['CONTENT_TYPE' => 'Some weird content type']),
            ['foo' => 'bar'],
            '{"foo":"bar"}',
        ];

        yield [
            Request::create('', 'GET', [], [], [], ['CONTENT_TYPE' => 'application/xml']),
            ['foo' => 'bar'],
            <<<DATA
<?xml version="1.0"?>
<response><foo>bar</foo></response>

DATA
        ];
    }

    /**
     * @return Generator<array{0: string}>
     */
    public function dataProviderTestThatNonSupportedSerializerFormatThrowsHttpException(): Generator
    {
        yield ['not supported format'];

        yield ['sjon'];

        yield ['lmx'];

        yield [''];
    }
}

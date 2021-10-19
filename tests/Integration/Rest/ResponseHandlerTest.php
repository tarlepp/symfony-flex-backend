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
use Exception;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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
class ResponseHandlerTest extends KernelTestCase
{
    public function testThatGetSerializerReturnsExpected(): void
    {
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $responseClass = new ResponseHandler($serializer);

        self::assertSame($serializer, $responseClass->getSerializer());
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
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $stubResourceService = $this->createMock(RestResourceInterface::class);

        $httpResponse = (new ResponseHandler($serializer))->createResponse($request, $data, $stubResourceService, 200);

        self::assertSame($expectedContent, $httpResponse->getContent());
    }

    /**
     * @throws Throwable
     */
    public function testThatCreateResponseThrowsAnExceptionIfSerializationFails(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Some exception');

        $stubSerializer = $this->createMock(SerializerInterface::class);

        $request = Request::create('');
        $exception = new Exception('Some exception');

        $stubSerializer
            ->expects(self::once())
            ->method('serialize')
            ->withAnyParameters()
            ->willThrowException($exception);

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

        $request = Request::create(
            '',
            'GET',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => $format,
            ]
        );

        $serializer = self::getContainer()->get(SerializerInterface::class);

        $stubResourceService = $this->createMock(RestResourceInterface::class);

        (new ResponseHandler($serializer))
            ->createResponse(
                $request,
                [
                    'foo' => 'bar',
                ],
                $stubResourceService,
                200,
                $format
            );
    }

    /**
     * @throws Throwable
     */
    public function testThatGetSerializeContextMethodCallsExpectedServiceMethods(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $request = $this->createMock(Request::class);
        $parameterBag = $this->createMock(ParameterBag::class);
        $restResource = $this->createMock(RestResourceInterface::class);

        $parameterBag
            ->expects(self::exactly(2))
            ->method('all')
            ->willReturn([]);

        $restResource
            ->expects(self::once())
            ->method('getEntityName')
            ->willReturn('FakeEntity');

        /** @var InputBag $parameterBag */
        $request->query = $parameterBag;

        $context = (new ResponseHandler($serializer))
            ->getSerializeContext($request, $restResource);

        self::assertSame(['FakeEntity'], $context['groups']);
    }

    /**
     * @throws Throwable
     */
    public function testThatGetSerializeContextSetExpectedGroupsWithPopulateAllParameterWhenNonAnyAssociations(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $request = $this->createMock(Request::class);
        $parameterBag = $this->createMock(ParameterBag::class);
        $restResource = $this->createMock(RestResourceInterface::class);

        $parameterBag
            ->expects(self::exactly(2))
            ->method('all')
            ->willReturn([
                'populateAll' => '',
            ]);

        $restResource
            ->expects(self::once())
            ->method('getEntityName')
            ->willReturn('FakeEntity');

        /** @var InputBag $parameterBag */
        $request->query = $parameterBag;

        $context = (new ResponseHandler($serializer))
            ->getSerializeContext($request, $restResource);

        self::assertSame(['FakeEntity'], $context['groups']);
    }

    /**
     * @throws Throwable
     */
    public function testThatGetSerializeContextSetExpectedGroupsWithPopulateAllParameterWhenAssociations(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $request = $this->createMock(Request::class);
        $parameterBag = $this->createMock(ParameterBag::class);
        $restResource = $this->createMock(RestResourceInterface::class);

        $parameterBag
            ->expects(self::exactly(2))
            ->method('all')
            ->willReturn([
                'populateAll' => '',
            ]);

        $restResource
            ->expects(self::once())
            ->method('getEntityName')
            ->willReturn('FakeEntity');

        $restResource
            ->expects(self::once())
            ->method('getAssociations')
            ->willReturn(['AnotherFakeEntity']);

        /** @var InputBag $parameterBag */
        $request->query = $parameterBag;

        $context = (new ResponseHandler($serializer))
            ->getSerializeContext($request, $restResource);

        self::assertSame(['FakeEntity', 'FakeEntity.AnotherFakeEntity'], $context['groups']);
    }

    /**
     * @throws Throwable
     */
    public function testThatGetSerializeContextSetExpectedGroupsWithPopulateOnlyParameterWhenNonAssociations(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $request = $this->createMock(Request::class);
        $parameterBag = $this->createMock(ParameterBag::class);
        $restResource = $this->createMock(RestResourceInterface::class);

        $parameterBag
            ->expects(self::exactly(2))
            ->method('all')
            ->willReturn([
                'populateOnly' => '',
            ]);

        $restResource
            ->expects(self::once())
            ->method('getEntityName')
            ->willReturn('FakeEntity');

        /** @var InputBag $parameterBag */
        $request->query = $parameterBag;

        $context = (new ResponseHandler($serializer))
            ->getSerializeContext($request, $restResource);

        self::assertSame(['FakeEntity'], $context['groups']);
    }

    /**
     * @throws Throwable
     */
    public function testThatGetSerializeContextSetExpectedGroupsWithPopulateOnlyParameterWhenEntityAssociations(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $request = $this->createMock(Request::class);
        $parameterBag = $this->createMock(ParameterBag::class);
        $restResource = $this->createMock(RestResourceInterface::class);

        $parameterBag
            ->expects(self::exactly(2))
            ->method('all')
            ->willReturn([
                'populateOnly' => '',
            ]);

        $restResource
            ->expects(self::once())
            ->method('getEntityName')
            ->willReturn('FakeEntity');

        $request
            ->expects(self::once())
            ->method('get')
            ->with('populate')
            ->willReturn(['AnotherFakeEntity']);

        /** @var InputBag $parameterBag */
        $request->query = $parameterBag;

        $context = (new ResponseHandler($serializer))
            ->getSerializeContext($request, $restResource);

        self::assertSame(['AnotherFakeEntity'], $context['groups']);
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
            ->expects(self::exactly(2))
            ->method('all')
            ->willReturn([
                'populateOnly' => '',
            ]);

        $restResource
            ->expects(self::once())
            ->method('getEntityName')
            ->willReturn('FakeEntity');

        $restResource
            ->expects(self::once())
            ->method('getSerializerContext')
            ->willReturn($expected);

        $request
            ->expects(self::once())
            ->method('get')
            ->with('populate')
            ->willReturn(['AnotherFakeEntity']);

        /** @var InputBag $parameterBag */
        $request->query = $parameterBag;

        self::assertSame(
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

        $serializer = $this->createMock(SerializerInterface::class);
        $formInterface = $this->getMockBuilder(FormInterface::class)->getMock();
        $formError = $this->createMock(FormError::class);

        $formErrorIterator = new FormErrorIterator($formInterface, [$formError]);

        $formInterface
            ->expects(self::once())
            ->method('getErrors')
            ->withAnyParameters()
            ->willReturn($formErrorIterator);

        $formInterface
            ->expects(self::once())
            ->method('getName')
            ->willReturn('foo');

        $formError
            ->expects(self::once())
            ->method('getOrigin')
            ->willReturn($formInterface);

        $formError
            ->expects(self::atLeast(1))
            ->method('getMessage')
            ->willReturn('test error');

        (new ResponseHandler($serializer))->handleFormError($formInterface);
    }

    /**
     * @throws Throwable
     */
    public function testThatHandleFormErrorThrowsExpectedExceptionWithoutProperty(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('test error');

        $serializer = $this->createMock(SerializerInterface::class);
        $formInterface = $this->getMockBuilder(FormInterface::class)->getMock();
        $formError = $this->createMock(FormError::class);

        // Create FormErrorIterator
        $formErrorIterator = new FormErrorIterator($formInterface, [$formError]);

        $formInterface
            ->expects(self::once())
            ->method('getErrors')
            ->withAnyParameters()
            ->willReturn($formErrorIterator);

        $formInterface
            ->expects(self::once())
            ->method('getName')
            ->willReturn('');

        $formError
            ->expects(self::once())
            ->method('getOrigin')
            ->willReturn($formInterface);

        $formError
            ->expects(self::atLeast(1))
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
            [
                'foo' => 'bar',
            ],
            '{"foo":"bar"}',
        ];

        yield [
            Request::create(
                '',
                'GET',
                [],
                [],
                [],
                [
                    'CONTENT_TYPE' => 'Some weird content type',
                ]
            ),
            [
                'foo' => 'bar',
            ],
            '{"foo":"bar"}',
        ];

        yield [
            Request::create(
                '',
                'GET',
                [],
                [],
                [],
                [
                    'CONTENT_TYPE' => 'application/xml',
                ]
            ),
            [
                'foo' => 'bar',
            ],
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

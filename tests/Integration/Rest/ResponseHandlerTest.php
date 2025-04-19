<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/ResponseHandlerTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Rest;

use App\Resource\ApiKeyResource;
use App\Rest\Interfaces\RestResourceInterface;
use App\Rest\ResponseHandler;
use Exception;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

/**
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
     * @param array<string, string> $data
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatCreateResponseReturnsExpected')]
    #[TestDox('Test that response is `$expectedContent` when using `$request` request with `$data` data.')]
    public function testThatCreateResponseReturnsExpected(
        Request $request,
        array $data,
        string $expectedContent
    ): void {
        $serializer = self::getContainer()->get(SerializerInterface::class);
        $stubResourceService = $this->createMock(RestResourceInterface::class);

        $httpResponse = new ResponseHandler($serializer)->createResponse($request, $data, $stubResourceService, 200);

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
            ->expects($this->once())
            ->method('serialize')
            ->withAnyParameters()
            ->willThrowException($exception);

        new ResponseHandler($stubSerializer)
            ->createResponse($request, []);
    }

    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatNonSupportedSerializerFormatThrowsHttpException')]
    #[TestDox('Test that non supported serializer format `$format` throws an exception.')]
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

        new ResponseHandler($serializer)
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
    public function testThatHandleFormErrorThrowsExpectedExceptionWithProperty(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Field \'foo\': test error');

        $serializer = $this->createMock(SerializerInterface::class);
        $formInterface = $this->getMockBuilder(FormInterface::class)->getMock();
        $formError = $this->createMock(FormError::class);

        $formErrorIterator = new FormErrorIterator($formInterface, [$formError]);

        $formInterface
            ->expects($this->once())
            ->method('getErrors')
            ->withAnyParameters()
            ->willReturn($formErrorIterator);

        $formInterface
            ->expects($this->once())
            ->method('getName')
            ->willReturn('foo');

        $formError
            ->expects($this->once())
            ->method('getOrigin')
            ->willReturn($formInterface);

        $formError
            ->expects(self::atLeast(1))
            ->method('getMessage')
            ->willReturn('test error');

        new ResponseHandler($serializer)->handleFormError($formInterface);
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
            ->expects($this->once())
            ->method('getErrors')
            ->withAnyParameters()
            ->willReturn($formErrorIterator);

        $formInterface
            ->expects($this->once())
            ->method('getName')
            ->willReturn('');

        $formError
            ->expects($this->once())
            ->method('getOrigin')
            ->willReturn($formInterface);

        $formError
            ->expects(self::atLeast(1))
            ->method('getMessage')
            ->willReturn('test error');

        new ResponseHandler($serializer)->handleFormError($formInterface);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `getSerializeContext` return expected when using `populateAll` query parameter')]
    public function testThatGetSerializeContextReturnsExpectedWhenUsingPopulateAll(): void
    {
        self::bootKernel();

        $serializer = $this->createMock(SerializerInterface::class);
        $resource = static::getContainer()->get(ApiKeyResource::class);

        $request = Request::create(
            '',
            parameters: [
                'populateAll' => true,
            ],
        );

        $output = new ResponseHandler($serializer)->getSerializeContext($request, $resource);

        $expectedContext = [
            'groups' => [
                'ApiKey',
                'ApiKey.userGroups',
                'ApiKey.logsRequest',
                'ApiKey.createdBy',
                'ApiKey.updatedBy',
            ],
        ];

        self::assertSame($expectedContext, $output);
    }

    /**
     * @throws Throwable
     */
    #[TestDox('Test that `getSerializeContext` return expected when using `populateOnly` query parameter')]
    public function testThatGetSerializeContextReturnsExpectedWhenUsingPopulateOnly(): void
    {
        self::bootKernel();

        $serializer = $this->createMock(SerializerInterface::class);
        $resource = static::getContainer()->get(ApiKeyResource::class);

        $request = Request::create(
            '',
            parameters: [
                'populateOnly' => true,
            ],
        );

        $output = new ResponseHandler($serializer)->getSerializeContext($request, $resource);

        $expectedContext = [
            'groups' => [
                'ApiKey',
            ],
        ];

        self::assertSame($expectedContext, $output);
    }

    /**
     * @return Generator<array{0: Request, 1: array<string, string>, 2: string}>
     */
    public static function dataProviderTestThatCreateResponseReturnsExpected(): Generator
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
    public static function dataProviderTestThatNonSupportedSerializerFormatThrowsHttpException(): Generator
    {
        yield ['not supported format'];

        yield ['sjon'];

        yield ['lmx'];

        yield [''];
    }
}

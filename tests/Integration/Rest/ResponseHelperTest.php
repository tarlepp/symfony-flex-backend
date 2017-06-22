<?php
declare(strict_types = 1);
/**
 * /tests/AppBundle/integration/Services/Rest/Helper/ResponseTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace AppBundle\integration\Services\Rest\Helper;

use App\Rest\ResourceInterface;
use App\Rest\ResponseHelper;
use App\Utils\Tests\ContainerTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class ResponseTest
 *
 * @package AppBundle\integration\Services\Rest\Helper
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ResponseHelperTest extends ContainerTestCase
{
    public function testThatGetSerializerReturnsExpected(): void
    {
        $serializer = $this->getContainer()->get('serializer');

        $responseClass = new ResponseHelper($serializer);

        static::assertSame($serializer,  $responseClass->getSerializer());
    }

    /**
     * @dataProvider dataProviderTestThatCreateResponseReturnsExpected
     *
     * @param   Request $request
     * @param   mixed   $data
     * @param   string  $expectedContent
     */
    public function testThatCreateResponseReturnsExpected(
        Request $request,
        $data,
        string $expectedContent
    ): void
    {
        $serializer = $this->getContainer()->get('serializer');

        /** @var ResourceInterface|\PHPUnit_Framework_MockObject_MockObject $stubResourceService */
        $stubResourceService = $this->createMock(ResourceInterface::class);

        $responseClass = new ResponseHelper($serializer);
        $responseClass->setResource($stubResourceService);

        $httpResponse = $responseClass->createResponse($request, $data, 200);

        static::assertSame($expectedContent, $httpResponse->getContent());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionMessage Some exception
     */
    public function testThatCreateResponseThrowsAnExceptionIfSerializationFails(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|SerializerInterface $stubSerializer
         * @var \PHPUnit_Framework_MockObject_MockObject|ResourceInterface   $stubResourceService
         */
        $stubSerializer = $this->createMock(SerializerInterface::class);
        $stubResourceService = $this->createMock(ResourceInterface::class);

        $request = Request::create('');

        $exception = new \Exception('Some exception');

        $stubSerializer
            ->expects(static::once())
            ->method('serialize')
            ->withAnyParameters()
            ->willThrowException($exception);

        $responseClass = new ResponseHelper($stubSerializer);
        $responseClass->setResource($stubResourceService);
        $responseClass->createResponse($request, []);
    }

    /**
     * @dataProvider dataProviderTestThatNonSupportedSerializerFormatThrowsHttpException
     *
     * @expectedException \Symfony\Component\HttpKernel\Exception\HttpException
     * @expectedExceptionCode 400
     * @expectedExceptionMessageRegExp /Serialization for the format .* is not supported/
     *
     * @param string $format
     */
    public function testThatNonSupportedSerializerFormatThrowsHttpException(string $format): void
    {
        $request = Request::create('', 'GET', [], [], [], ['CONTENT_TYPE' => $format]);
        $serializer = $this->getContainer()->get('serializer');

        /** @var ResourceInterface|\PHPUnit_Framework_MockObject_MockObject $stubResourceService */
        $stubResourceService = $this->createMock(ResourceInterface::class);

        $responseClass = new ResponseHelper($serializer);
        $responseClass->setResource($stubResourceService);

        $responseClass->createResponse($request, ['foo' => 'bar'], 200, $format);
    }

    public function testThatGetSerializeContextMethodCallsExpectedServiceMethods():void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|SerializerInterface $stubSerializer
         * @var \PHPUnit_Framework_MockObject_MockObject|Request             $stubRequest
         * @var \PHPUnit_Framework_MockObject_MockObject|ParameterBag        $stubParameterBag
         * @var \PHPUnit_Framework_MockObject_MockObject|ResourceInterface   $stubResourceService
         */
        $stubSerializer = $this->createMock(SerializerInterface::class);
        $stubRequest = $this->createMock(Request::class);
        $stubParameterBag = $this->createMock(ParameterBag::class);
        $stubResourceService = $this->createMock(ResourceInterface::class);

        $stubParameterBag
            ->expects(static::exactly(2))
            ->method('all')
            ->willReturn([]);

        $stubResourceService
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn('FakeEntity');

        $stubRequest->query = $stubParameterBag;

        $testClass = new ResponseHelper($stubSerializer);
        $testClass->setResource($stubResourceService);
        $context = $testClass->getSerializeContext($stubRequest);

        static::assertSame(['FakeEntity'], $context['groups']);
    }

    public function testThatGetSerializeContextSetExpectedGroupsWithPopulateAllParameterWhenEntityDoesNotHaveAnyAssociations(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|SerializerInterface                 $stubSerializer
         * @var \PHPUnit_Framework_MockObject_MockObject|Request                    $stubRequest
         * @var \PHPUnit_Framework_MockObject_MockObject|ParameterBag               $stubParameterBag
         * @var \PHPUnit_Framework_MockObject_MockObject|ResourceInterface   $stubResourceService
         */
        $stubSerializer = $this->createMock(SerializerInterface::class);
        $stubRequest = $this->createMock(Request::class);
        $stubParameterBag = $this->createMock(ParameterBag::class);
        $stubResourceService = $this->createMock(ResourceInterface::class);

        $stubParameterBag
            ->expects(static::exactly(2))
            ->method('all')
            ->willReturn(['populateAll' => '']);

        $stubResourceService
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn('FakeEntity');

        $stubRequest->query = $stubParameterBag;

        $testClass = new ResponseHelper($stubSerializer);
        $testClass->setResource($stubResourceService);
        $context = $testClass->getSerializeContext($stubRequest);

        static::assertSame(['Default'], $context['groups']);
    }

    public function testThatGetSerializeContextSetExpectedGroupsWithPopulateAllParameterWhenEntityDoesHaveAssociations(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|SerializerInterface                 $stubSerializer
         * @var \PHPUnit_Framework_MockObject_MockObject|Request                    $stubRequest
         * @var \PHPUnit_Framework_MockObject_MockObject|ParameterBag               $stubParameterBag
         * @var \PHPUnit_Framework_MockObject_MockObject|ResourceInterface   $stubResourceService
         */
        $stubSerializer = $this->createMock(SerializerInterface::class);
        $stubRequest = $this->createMock(Request::class);
        $stubParameterBag = $this->createMock(ParameterBag::class);
        $stubResourceService = $this->createMock(ResourceInterface::class);

        $stubParameterBag
            ->expects(static::exactly(2))
            ->method('all')
            ->willReturn(['populateAll' => '']);

        $stubResourceService
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn('FakeEntity');

        $stubResourceService
            ->expects(static::once())
            ->method('getAssociations')
            ->willReturn(['AnotherFakeEntity']);

        $stubRequest->query = $stubParameterBag;

        $testClass = new ResponseHelper($stubSerializer);
        $testClass->setResource($stubResourceService);
        $context = $testClass->getSerializeContext($stubRequest);

        static::assertSame(['Default', 'FakeEntity.AnotherFakeEntity'], $context['groups']);
    }

    public function testThatGetSerializeContextSetExpectedGroupsWithPopulateOnlyParameterWhenEntityDoesNotHaveAnyAssociations(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|SerializerInterface                 $stubSerializer
         * @var \PHPUnit_Framework_MockObject_MockObject|Request                    $stubRequest
         * @var \PHPUnit_Framework_MockObject_MockObject|ParameterBag               $stubParameterBag
         * @var \PHPUnit_Framework_MockObject_MockObject|ResourceInterface   $stubResourceService
         */
        $stubSerializer = $this->createMock(SerializerInterface::class);
        $stubRequest = $this->createMock(Request::class);
        $stubParameterBag = $this->createMock(ParameterBag::class);
        $stubResourceService = $this->createMock(ResourceInterface::class);

        $stubParameterBag
            ->expects(static::exactly(2))
            ->method('all')
            ->willReturn(['populateOnly' => '']);

        $stubResourceService
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn('FakeEntity');

        $stubRequest->query = $stubParameterBag;

        $testClass = new ResponseHelper($stubSerializer);
        $testClass->setResource($stubResourceService);
        $context = $testClass->getSerializeContext($stubRequest);

        static::assertSame(['FakeEntity'], $context['groups']);
    }

    public function testThatGetSerializeContextSetExpectedGroupsWithPopulateOnlyParameterWhenEntityDoesHaveAssociations(): void
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|SerializerInterface                 $stubSerializer
         * @var \PHPUnit_Framework_MockObject_MockObject|Request                    $stubRequest
         * @var \PHPUnit_Framework_MockObject_MockObject|ParameterBag               $stubParameterBag
         * @var \PHPUnit_Framework_MockObject_MockObject|ResourceInterface   $stubResourceService
         */
        $stubSerializer = $this->createMock(SerializerInterface::class);
        $stubRequest = $this->createMock(Request::class);
        $stubParameterBag = $this->createMock(ParameterBag::class);
        $stubResourceService = $this->createMock(ResourceInterface::class);

        $stubParameterBag
            ->expects(static::exactly(2))
            ->method('all')
            ->willReturn(['populateOnly' => '']);

        $stubResourceService
            ->expects(static::once())
            ->method('getEntityName')
            ->willReturn('FakeEntity');

        $stubRequest
            ->expects(static::once())
            ->method('get')
            ->with('populate')
            ->willReturn(['AnotherFakeEntity']);

        $stubRequest->query = $stubParameterBag;

        $testClass = new ResponseHelper($stubSerializer);
        $testClass->setResource($stubResourceService);
        $context = $testClass->getSerializeContext($stubRequest);

        static::assertSame(['AnotherFakeEntity'], $context['groups']);
    }

    /**
     * @return array
     */
    public function dataProviderTestThatCreateResponseReturnsExpected(): array
    {
        return [
            [
                Request::create(''),
                ['foo' => 'bar'],
                '{"foo":"bar"}'
            ],
            [
                Request::create('', 'GET', [], [], [], ['CONTENT_TYPE' => 'Some weird content type']),
                ['foo' => 'bar'],
                '{"foo":"bar"}'
            ],
            [
                Request::create('', 'GET', [], [], [], ['CONTENT_TYPE' => 'application/xml']),
                ['foo' => 'bar'],
                <<<DATA
<?xml version="1.0"?>
<response><foo>bar</foo></response>

DATA
            ],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderTestThatNonSupportedSerializerFormatThrowsHttpException(): array
    {
        return [
            ['not supported format'],
            ['sjon'],
            ['lmx'],
        ];
    }
}

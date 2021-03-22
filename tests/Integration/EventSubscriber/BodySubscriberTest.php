<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/BodySubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\EventSubscriber\BodySubscriber;
use App\Utils\Tests\StringableArrayObject;
use Generator;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class BodySubscriberTest
 *
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class BodySubscriberTest extends KernelTestCase
{
    /**
     * @throws JsonException
     */
    public function testThatEmptyBodyWorksLikeExpected(): void
    {
        static::bootKernel();

        $request = new Request();

        $event = new RequestEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        (new BodySubscriber())
            ->onKernelRequest($event);

        static::assertEmpty($request->query->all());
        static::assertEmpty($request->request->all());
    }

    /**
     * @throws JsonException
     */
    public function testThatNonJsonContentTypeWorksLikeExpected(): void
    {
        static::bootKernel();

        $inputQuery = [
            'foo' => 'bar',
        ];

        $inputRequest = [
            'bar' => 'foo',
        ];

        $request = new Request($inputQuery, $inputRequest, [], [], [], [], 'Some content');
        $request->headers->set('Content-Type', 'text/xml');

        $event = new RequestEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        (new BodySubscriber())
            ->onKernelRequest($event);

        static::assertSame($inputQuery, $request->query->all());
        static::assertSame($inputRequest, $request->request->all());
    }

    /**
     * @dataProvider dataProviderTestThatJsonContentReplaceParametersAsExpected
     *
     * @phpstan-param StringableArrayObject<array<mixed>> $expectedParameters
     * @psalm-param StringableArrayObject $expectedParameters
     *
     * @throws JsonException
     *
     * @testdox Test that subscriber converts `$content` content with `$contentType` type to `$expectedParameters`.
     */
    public function testThatJsonContentReplaceParametersAsExpected(
        StringableArrayObject $expectedParameters,
        string $contentType,
        string $content
    ): void {
        static::bootKernel();

        $request = new Request([], ['foobar' => 'foobar'], [], [], [], [], $content);
        $request->headers->set('Content-Type', $contentType);

        $event = new RequestEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $subscriber = new BodySubscriber();
        $subscriber->onKernelRequest($event);

        static::assertSame($expectedParameters->getArrayCopy(), $request->request->all());
    }

    /**
     * @throws JsonException
     */
    public function testThatInvalidJsonContentThrowsAnException(): void
    {
        $this->expectException(JsonException::class);

        static::bootKernel();

        $request = new Request([], [], [], [], [], [], '{"Some": "not", "valid" JSON}');

        $event = new RequestEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $subscriber = new BodySubscriber();
        $subscriber->onKernelRequest($event);
    }

    /**
     * @throws JsonException
     */
    public function testThatWithEmptyBodyReplaceIsNotCalled(): void
    {
        static::bootKernel();

        $request = $this->getMockBuilder(Request::class)->getMock();
        $parameterBag = $this->getMockBuilder(ParameterBag::class)->getMock();

        $request
            ->expects(static::once())
            ->method('getContent')
            ->willReturn('');

        $parameterBag
            ->expects(static::never())
            ->method('replace');

        /**
         * @var InputBag $parameterBag
         */
        $request->request = $parameterBag;

        $event = new RequestEvent(static::$kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $subscriber = new BodySubscriber();
        $subscriber->onKernelRequest($event);
    }

    /**
     * @return Generator<array{0: StringableArrayObject, 1: string, 2:  string}>
     */
    public function dataProviderTestThatJsonContentReplaceParametersAsExpected(): Generator
    {
        yield [
            new StringableArrayObject(['foo' => 'bar']),
            '',
            '{"foo": "bar"}',
        ];

        yield [
            new StringableArrayObject(['foo' => 'bar']),
            'application/json',
            '{"foo": "bar"}',
        ];

        yield [
            new StringableArrayObject(['foo' => 'bar']),
            'application/x-json',
            '{"foo": "bar"}',
        ];

        yield [
            new StringableArrayObject(['foo' => 'bar']),
            'text/plain',
            '{"foo": "bar"}',
        ];
    }
}

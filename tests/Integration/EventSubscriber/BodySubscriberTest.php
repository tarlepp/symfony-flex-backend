<?php
declare(strict_types = 1);
/**
 * /tests/Integration/EventSubscriber/BodySubscriberTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\EventSubscriber;

use App\EventSubscriber\BodySubscriber;
use App\Tests\Utils\StringableArrayObject;
use Generator;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @package App\Tests\Integration\EventSubscriber
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class BodySubscriberTest extends KernelTestCase
{
    /**
     * @throws JsonException
     */
    #[TestDox('Test that `empty` body works like expected')]
    public function testThatEmptyBodyWorksLikeExpected(): void
    {
        self::bootKernel();

        $request = new Request();

        $event = new RequestEvent(self::$kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        (new BodySubscriber())
            ->onKernelRequest($event);

        self::assertEmpty($request->query->all());
        self::assertEmpty($request->request->all());
    }

    /**
     * @throws JsonException
     */
    #[TestDox('Test that `non` JSON content type works like expected')]
    public function testThatNonJsonContentTypeWorksLikeExpected(): void
    {
        self::bootKernel();

        $inputQuery = [
            'foo' => 'bar',
        ];

        $inputRequest = [
            'bar' => 'foo',
        ];

        $request = new Request($inputQuery, $inputRequest, [], [], [], [], 'Some content');
        $request->headers->set('Content-Type', 'text/xml');

        $event = new RequestEvent(self::$kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        (new BodySubscriber())
            ->onKernelRequest($event);

        self::assertSame($inputQuery, $request->query->all());
        self::assertSame($inputRequest, $request->request->all());
    }

    /**
     * @phpstan-param StringableArrayObject<mixed> $expectedParameters
     * @psalm-param StringableArrayObject $expectedParameters
     *
     * @throws JsonException
     */
    #[DataProvider('dataProviderTestThatJsonContentReplaceParametersAsExpected')]
    #[TestDox('Test that subscriber converts `$content` content with `$contentType` type to `$expectedParameters`.')]
    public function testThatJsonContentReplaceParametersAsExpected(
        StringableArrayObject $expectedParameters,
        string $contentType,
        string $content
    ): void {
        self::bootKernel();

        $request = new Request(
            [],
            [
                'foobar' => 'foobar',
            ],
            [],
            [],
            [],
            [],
            $content
        );
        $request->headers->set('Content-Type', $contentType);

        $event = new RequestEvent(self::$kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber = new BodySubscriber();
        $subscriber->onKernelRequest($event);

        self::assertSame($expectedParameters->getArrayCopy(), $request->request->all());
    }

    /**
     * @throws JsonException
     */
    #[TestDox('Test that invalid JSON content throws an exception')]
    public function testThatInvalidJsonContentThrowsAnException(): void
    {
        $this->expectException(JsonException::class);

        self::bootKernel();

        $request = new Request([], [], [], [], [], [], '{"Some": "not", "valid" JSON}');

        $event = new RequestEvent(self::$kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber = new BodySubscriber();
        $subscriber->onKernelRequest($event);
    }

    /**
     * @throws JsonException
     */
    #[TestDox('Test that with empty body replace is not called')]
    public function testThatWithEmptyBodyReplaceIsNotCalled(): void
    {
        self::bootKernel();

        $request = $this->getMockBuilder(Request::class)->getMock();
        $parameterBag = $this->getMockBuilder(ParameterBag::class)->getMock();

        $request
            ->expects(self::once())
            ->method('getContent')
            ->willReturn('');

        $parameterBag
            ->expects(self::never())
            ->method('replace');

        /** @var InputBag $parameterBag */
        $request->request = $parameterBag; // @phpstan-ignore-line

        $event = new RequestEvent(self::$kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $subscriber = new BodySubscriber();
        $subscriber->onKernelRequest($event);
    }

    /**
     * @psalm-return Generator<array{0: StringableArrayObject, 1: string, 2:  string}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: string, 2:  string}>
     */
    public static function dataProviderTestThatJsonContentReplaceParametersAsExpected(): Generator
    {
        yield [
            new StringableArrayObject([
                'foo' => 'bar',
            ]),
            '',
            '{"foo": "bar"}',
        ];

        yield [
            new StringableArrayObject([
                'foo' => 'bar',
            ]),
            'application/json',
            '{"foo": "bar"}',
        ];

        yield [
            new StringableArrayObject([
                'foo' => 'bar',
            ]),
            'application/x-json',
            '{"foo": "bar"}',
        ];

        yield [
            new StringableArrayObject([
                'foo' => 'bar',
            ]),
            'text/plain',
            '{"foo": "bar"}',
        ];
    }
}

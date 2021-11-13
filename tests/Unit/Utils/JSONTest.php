<?php

declare(strict_types = 1);
/**
 * /tests/Unit/Utils/JSONTest.php.
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Utils;

use App\Utils\JSON;
use App\Utils\Tests\StringableArrayObject;
use Generator;
use JsonException;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function is_array;
use function serialize;

/**
 * Class JSONTest.
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class JSONTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatEncodeWorksLikeExpected
     *
     * @throws JsonException
     *
     * @testdox Test that `JSON::encode` method returns `$expected` when using `$value` as input
     */
    public function testThatEncodeWorksLikeExpected(mixed $value, mixed $expected): void
    {
        self::assertSame($expected, JSON::encode($value));
    }

    /**
     * @dataProvider dataProviderTestThatDecodeWorksLikeExpected
     *
     * @phpstan-param  StringableArrayObject<string> $parameters
     * @psalm-param  StringableArrayObject $parameters
     *
     * @throws JsonException
     *
     * @testdox Test that `JSON::decode` method returns `$expected` when using `$parameters` as input
     */
    public function testThatDecodeWorksLikeExpected(StringableArrayObject $parameters, mixed $expected): void
    {
        /** @psalm-suppress InvalidArgument */
        self::assertSame(
            serialize($expected),
            serialize(JSON::decode(...$parameters))
        );
    }

    /**
     * @throws JsonException
     *
     * @testdox Test that `JSON::encode` method throws exception when maximum stack depth exceeded
     */
    public function testThatEncodeThrowsAnExceptionOnMaximumDepth(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Maximum stack depth exceeded');

        $arguments = [
            [
                'foo' => [
                    'bar' => [
                        'foo' => [
                            'bar' => 'foo',
                        ],
                    ],
                ],
            ],
            0,
            3,
        ];

        JSON::encode(...$arguments);
    }

    /**
     * @throws JsonException
     *
     * @testdox Test that `JSON::decode` method throws exception when maximum stack depth exceeded
     */
    public function testThatDecodeThrowsAnExceptionOnMaximumDepth(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Maximum stack depth exceeded');

        $arguments = [
            '{"bar":"foo","foo":{"a":"foobar","b":{"c":2}}}',
            false,
            3,
        ];

        JSON::decode(...$arguments);
    }

    /**
     * @dataProvider dataProviderTestThatDecodeThrowsAnExceptionOnMalformedJson
     *
     * @throws JsonException
     *
     * @testdox Test that `JSON::decode` method throws an exception with malformed JSON: `$json`
     */
    public function testThatDecodeThrowsAnExceptionOnMalformedJson(string $json): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        JSON::decode($json);
    }

    /**
     * @dataProvider dataProviderTestThatEncodeThrowsAnExceptionOnInvalidUtfCharacters
     *
     * @throws JsonException
     *
     * @testdox Test that `JSON::decode` method throws an exception with invalid UTF characters in JSON: `$input`
     */
    public function testThatEncodeThrowsAnExceptionOnInvalidUtfCharacters(string $input): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Malformed UTF-8 characters, possibly incorrectly encoded');

        JSON::encode($input);
    }

    /**
     * Data provider for 'testThatEncodeWorksLikeExpected'.
     *
     * @return Generator<array{0: mixed, 1: string}>
     */
    public function dataProviderTestThatEncodeWorksLikeExpected(): Generator
    {
        yield [
            null,
            'null',
        ];

        yield [
            true,
            'true',
        ];

        yield [
            false,
            'false',
        ];

        yield [
            [
                'foo' => 'bar',
            ],
            '{"foo":"bar"}',
        ];

        // Create simple object for test
        $object = new stdClass();
        $object->bar = 'foo';
        $object->foo = new stdClass();
        $object->foo->a = 'foobar';
        $object->foo->b = 12;
        $object->foo->c = '12';
        $object->foo->d = true;

        yield [
            $object,
            '{"bar":"foo","foo":{"a":"foobar","b":12,"c":"12","d":true}}',
        ];
    }

    /**
     * Data provider for 'testThatDecodeWorksLikeExpected'.
     *
     * @return Generator<array<int, mixed>>
     */
    public function dataProviderTestThatDecodeWorksLikeExpected(): Generator
    {
        $iterator = static fn (array $data): array => [
            new StringableArrayObject([$data[1], is_array($data[0])]),
            $data[0],
        ];

        foreach ($this->dataProviderTestThatEncodeWorksLikeExpected() as $data) {
            yield $iterator($data);
        }
    }

    /**
     * Data provider for 'testThatDecodeThrowsAnExceptionOnMalformedJson'.
     *
     * @return Generator<array{0: string}>
     */
    public function dataProviderTestThatDecodeThrowsAnExceptionOnMalformedJson(): Generator
    {
        yield ['{foo:bar}'];
        yield ["{'foo':'bar'}"];
        yield ['{"foo":bar}'];
        yield ['{"foo":}'];
    }

    /**
     * Data provider for 'testThatEncodeThrowsAnExceptionOnInvalidUtfCharacters'.
     *
     * @return Generator<array{0: string}>
     */
    public function dataProviderTestThatEncodeThrowsAnExceptionOnInvalidUtfCharacters(): Generator
    {
        yield ["\xB1\x31"];
        yield [mb_convert_encoding('{"data":"äöäö"}', 'ISO-8859-15', 'UTF8')];
    }
}

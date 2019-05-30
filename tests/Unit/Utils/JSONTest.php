<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Utils/JSONTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\Utils;

use App\Utils\JSON;
use Generator;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function is_array;
use function serialize;

/**
 * Class JSONTest
 *
 * @package App\Tests\Unit\Utils
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class JSONTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatEncodeWorksLikeExpected
     *
     * @param   mixed $value
     * @param   mixed $expected
     */
    public function testThatEncodeWorksLikeExpected($value, $expected): void
    {
        static::assertSame($expected, JSON::encode($value));
    }

    /**
     * @dataProvider dataProviderTestThatDecodeWorksLikeExpected
     *
     * @param   array $parameters
     * @param   mixed $expected
     */
    public function testThatDecodeWorksLikeExpected(array $parameters, $expected): void
    {
        static::assertSame(
            serialize($expected),
            serialize(JSON::decode(...$parameters))
        );
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @dataProvider dataProviderTestThatEncodeThrowsAnExceptionOnMaximumDepth
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Maximum stack depth exceeded
     *
     * @param array $arguments
     */
    public function testThatEncodeThrowsAnExceptionOnMaximumDepth(array $arguments): void
    {
        JSON::encode(...$arguments);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @dataProvider dataProviderTestThatDecodeThrowsAnExceptionOnMaximumDepth
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Maximum stack depth exceeded
     *
     * @param array $arguments
     */
    public function testThatDecodeThrowsAnExceptionOnMaximumDepth(array $arguments): void
    {
        JSON::decode(...$arguments);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @dataProvider dataProviderTestThatDecodeThrowsAnExceptionOnMalformedJson
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Syntax error, malformed JSON
     *
     * @param string $json
     */
    public function testThatDecodeThrowsAnExceptionOnMalformedJson(string $json): void
    {
        JSON::decode($json);
    }

    /** @noinspection PhpFullyQualifiedNameUsageInspection */
    /**
     * @dataProvider dataProviderTestThatEncodeThrowsAnExceptionOnInvalidUtfCharacters
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Malformed UTF-8 characters, possibly incorrectly encoded
     *
     * @param string $input
     */
    public function testThatEncodeThrowsAnExceptionOnInvalidUtfCharacters(string $input): void
    {
        JSON::encode($input);
    }

    /**
     * Data provider for 'testThatEncodeWorksLikeExpected'
     *
     * @return Generator
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
            ['foo' => 'bar'],
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
     * Data provider for 'testThatDecodeWorksLikeExpected'
     *
     * @return Generator
     */
    public function dataProviderTestThatDecodeWorksLikeExpected(): Generator
    {
        $iterator = static function ($data) {
            return [
                [$data[1], is_array($data[0]) ? true : false],
                $data[0],
            ];
        };

        foreach ($this->dataProviderTestThatEncodeWorksLikeExpected() as $data) {
            yield $iterator($data);
        }
    }

    /**
     * Date provider for 'testThatEncodeThrowsAnExceptionOnMaximumDepth'
     *
     * @return Generator
     */
    public function dataProviderTestThatEncodeThrowsAnExceptionOnMaximumDepth(): Generator
    {
        yield [
            [
                ['foo' => ['bar' => ['foo' => ['bar' => 'foo']]]],
                0,
                3,
            ]
        ];
    }

    /**
     * Data provider for 'testThatDecodeThrowsAnExceptionOnMaximumDepth'
     *
     * @return Generator
     */
    public function dataProviderTestThatDecodeThrowsAnExceptionOnMaximumDepth(): Generator
    {
        yield [
            [
                '{"bar":"foo","foo":{"a":"foobar","b":{"c":2}}}',
                false,
                3,
            ]
        ];
    }

    /**
     * Data provider for 'testThatDecodeThrowsAnExceptionOnMalformedJson'
     *
     * @return Generator
     */
    public function dataProviderTestThatDecodeThrowsAnExceptionOnMalformedJson(): Generator
    {
        yield ['{foo:bar}'];
        yield ["{'foo':'bar'}"];
        yield ['{"foo":bar}'];
        yield ['{"foo":}'];
    }

    /**
     * Data provider for 'testThatEncodeThrowsAnExceptionOnInvalidUtfCharacters'
     *
     * @return Generator
     */
    public function dataProviderTestThatEncodeThrowsAnExceptionOnInvalidUtfCharacters(): Generator
    {
        yield ["\xB1\x31"];
        yield [mb_convert_encoding('{"data":"äöäö"}', 'ISO-8859-15', 'UTF8')];
    }
}

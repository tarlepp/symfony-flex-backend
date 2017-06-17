<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Utils/JSONTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Unit\Utils;

use App\Utils\JSON;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class JSONTest
 *
 * @package AppBundle\unit\Utils
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
            \serialize($expected),
            \serialize(JSON::decode(...$parameters))
        );
    }

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
     * @return array
     */
    public function dataProviderTestThatEncodeWorksLikeExpected(): array
    {
        // Create simple object for test
        $object = new \stdClass();
        $object->bar = 'foo';
        $object->foo = new \stdClass();
        $object->foo->a = 'foobar';
        $object->foo->b = 12;
        $object->foo->c = '12';
        $object->foo->d = true;

        return [
            [
                null,
                'null',
            ],
            [
                true,
                'true',
            ],
            [
                false,
                'false',
            ],
            [
                ['foo' => 'bar'],
                '{"foo":"bar"}',
            ],
            [
                $object,
                '{"bar":"foo","foo":{"a":"foobar","b":12,"c":"12","d":true}}',
            ],
        ];
    }

    /**
     * Data provider for 'testThatDecodeWorksLikeExpected'
     *
     * @return array
     */
    public function dataProviderTestThatDecodeWorksLikeExpected(): array
    {
        $iterator = function ($data) {
            return [
                [$data[1], \is_array($data[0]) ? true : false],
                $data[0],
            ];
        };

        return \array_map($iterator, $this->dataProviderTestThatEncodeWorksLikeExpected());
    }

    /**
     * Date provider for 'testThatEncodeThrowsAnExceptionOnMaximumDepth'
     *
     * @return array
     */
    public function dataProviderTestThatEncodeThrowsAnExceptionOnMaximumDepth(): array
    {
        return [
            [
                [
                    ['foo' => ['bar' => ['foo' => ['bar' => 'foo']]]],
                    0,
                    3,
                ]
            ],
        ];
    }

    /**
     * Data provider for 'testThatDecodeThrowsAnExceptionOnMaximumDepth'
     *
     * @return array
     */
    public function dataProviderTestThatDecodeThrowsAnExceptionOnMaximumDepth(): array
    {
        return [
            [
                [
                    '{"bar":"foo","foo":{"a":"foobar","b":{"c":2}}}',
                    false,
                    3,
                ]
            ],
        ];
    }

    /**
     * Data provider for 'testThatDecodeThrowsAnExceptionOnMalformedJson'
     *
     * @return array
     */
    public function dataProviderTestThatDecodeThrowsAnExceptionOnMalformedJson(): array
    {
        return [
            ['{foo:bar}'],
            ["{'foo':'bar'}"],
            ['{"foo":bar}'],
            ['{"foo":}'],
        ];
    }

    /**
     * Data provider for 'testThatEncodeThrowsAnExceptionOnInvalidUtfCharacters'
     *
     * @return array
     */
    public function dataProviderTestThatEncodeThrowsAnExceptionOnInvalidUtfCharacters(): array
    {
        return [
            ["\xB1\x31"],
            [mb_convert_encoding('{"data":"äöäö"}', 'ISO-8859-15', 'UTF8')]
        ];
    }
}

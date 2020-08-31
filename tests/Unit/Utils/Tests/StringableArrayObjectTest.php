<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Utils/Tests/StringableArrayObjectTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Utils\Tests;

use App\Utils\Tests\StringableArrayObject;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class StringableArrayObjectTest
 *
 * @package App\Tests\Unit\Utils\Tests
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class StringableArrayObjectTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatCastingToStringReturnsExpected
     *
     * @testdox Test that casting to string with `$input` input (array converted to JSON) returns `$expected`
     */
    public function testThatCastingToStringReturnsExpected(StringableArrayObject $input, string $expected): void
    {
        static::assertSame($expected, (string)(new StringableArrayObject($input)));
    }

    public function dataProviderTestThatCastingToStringReturnsExpected(): Generator
    {
        yield [
            new StringableArrayObject(['test' => 'foo']),
            '{"test":"foo"}',
        ];

        yield [
            new StringableArrayObject(['test' => 1]),
            '{"test":1}',
        ];

        yield [
            new StringableArrayObject(['test' => false]),
            '{"test":false}',
        ];

        yield [
            new StringableArrayObject(['test' => null]),
            '{"test":null}',
        ];
    }
}

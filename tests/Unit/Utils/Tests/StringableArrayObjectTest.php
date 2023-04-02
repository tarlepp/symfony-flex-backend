<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Utils/Tests/StringableArrayObjectTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Utils\Tests;

use App\Tests\Utils\StringableArrayObject;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
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
     * @phpstan-param StringableArrayObject<array<string, string>> $input
     * @psalm-param StringableArrayObject $input
     */
    #[DataProvider('dataProviderTestThatCastingToStringReturnsExpected')]
    #[TestDox('Test that casting to string with `$input` input (array converted to JSON) returns `$expected`')]
    public function testThatCastingToStringReturnsExpected(StringableArrayObject $input, string $expected): void
    {
        self::assertSame($expected, (string)(new StringableArrayObject($input)));
    }

    /**
     * @psalm-return Generator<array{0: StringableArrayObject, 1: string}>
     * @phpstan-return Generator<array{0: StringableArrayObject<mixed>, 1: string}>
     */
    public static function dataProviderTestThatCastingToStringReturnsExpected(): Generator
    {
        yield [
            new StringableArrayObject([
                'test' => 'foo',
            ]),
            '{"test":"foo"}',
        ];

        yield [
            new StringableArrayObject([
                'test' => 1,
            ]),
            '{"test":1}',
        ];

        yield [
            new StringableArrayObject([
                'test' => false,
            ]),
            '{"test":false}',
        ];

        yield [
            new StringableArrayObject([
                'test' => null,
            ]),
            '{"test":null}',
        ];
    }
}

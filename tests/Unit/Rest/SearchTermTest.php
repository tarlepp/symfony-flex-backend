<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Rest/SearchTermTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Unit\Rest;

use App\Rest\SearchTerm;
use App\Utils\Tests\StringableArrayObject;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function call_user_func_array;

/**
 * Class SearchTermTest
 *
 * @package App\Tests\Unit\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class SearchTermTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatWithoutColumnOrSearchTermCriteriaIsNull
     *
     * @param mixed $column
     * @param mixed $search
     *
     * @testdox Test that `getCriteria` method returns null with `$column` + `$search` parameters.
     */
    public function testThatWithoutColumnOrSearchTermCriteriaIsNull($column, $search): void
    {
        static::assertNull(SearchTerm::getCriteria(
            $column instanceof StringableArrayObject ? $column->getArrayCopy() : $column,
            $search instanceof StringableArrayObject ? $search->getArrayCopy() : $search
        ), 'Criteria was not NULL with given parameters');
    }

    /**
     * @dataProvider dataProviderTestThatReturnedCriteriaIsExpected
     *
     * @testdox Test that `getCriteria` returns `$expected` with given `$inputArguments` arguments.
     */
    public function testThatReturnedCriteriaIsExpected(
        StringableArrayObject $inputArguments,
        StringableArrayObject $expected
    ): void {
        static::assertSame(
            $expected->getArrayCopy(),
            call_user_func_array([SearchTerm::class, 'getCriteria'], $inputArguments->getArrayCopy())
        );
    }

    /**
     * Data provider for testThatWithoutColumnOrSearchTermCriteriaIsNull
     */
    public function dataProviderTestThatWithoutColumnOrSearchTermCriteriaIsNull(): Generator
    {
        yield [null, null];
        yield ['foo', null];
        yield [null, 'foo'];
        yield ['', ''];
        yield [' ', ''];
        yield ['', ' '];
        yield [' ', ' '];
        yield ['foo', ''];
        yield ['foo', ' '];
        yield ['', 'foo'];
        yield [' ', 'foo'];
        yield [new StringableArrayObject([]), new StringableArrayObject([])];
        yield [new StringableArrayObject([null]), new StringableArrayObject([null])];
        yield [new StringableArrayObject(['foo']), new StringableArrayObject([null])];
        yield [new StringableArrayObject([null]), new StringableArrayObject(['foo'])];
        yield [new StringableArrayObject(['']), new StringableArrayObject([''])];
        yield [new StringableArrayObject([' ']), new StringableArrayObject([''])];
        yield [new StringableArrayObject(['']), new StringableArrayObject([' '])];
        yield [new StringableArrayObject([' ']), new StringableArrayObject([' '])];
        yield [new StringableArrayObject(['foo']), new StringableArrayObject([''])];
        yield [new StringableArrayObject(['foo']), new StringableArrayObject([' '])];
        yield [new StringableArrayObject(['']), new StringableArrayObject(['foo'])];
        yield [new StringableArrayObject([' ']), new StringableArrayObject(['foo'])];
    }

    /**
     * Data provider for testThatReturnedCriteriaIsExpected
     */
    public function dataProviderTestThatReturnedCriteriaIsExpected(): Generator
    {
        yield [
            new StringableArrayObject(['c1', 'word']),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject([['c1', 'c2'], ['search', 'word']]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['entity.c2', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                        ['entity.c2', 'like', '%word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject([['c1', 'c2'], 'search word']),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['entity.c2', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                        ['entity.c2', 'like', '%word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['someTable.c1', 'search word']),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['someTable.c1', 'like', '%search%'],
                        ['someTable.c1', 'like', '%word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['someTable.c1', ['search', 'word']]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['someTable.c1', 'like', '%search%'],
                        ['someTable.c1', 'like', '%word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject([['c1', 'someTable.c1'], 'search word']),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['someTable.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                        ['someTable.c1', 'like', '%word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject([['c1', 'someTable.c1'], ['search', 'word']]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['someTable.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                        ['someTable.c1', 'like', '%word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', 'search word', SearchTerm::OPERAND_AND]),
            new StringableArrayObject([
                'and' => [
                    'and' => [
                        ['entity.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', 'search word', SearchTerm::OPERAND_OR]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', 'search word', 'notSupportedOperand']),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', 'search word', SearchTerm::OPERAND_OR, SearchTerm::MODE_FULL]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', 'search word', SearchTerm::OPERAND_OR, SearchTerm::MODE_STARTS_WITH]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', 'search%'],
                        ['entity.c1', 'like', 'word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', 'search word', SearchTerm::OPERAND_OR, SearchTerm::MODE_ENDS_WITH]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search'],
                        ['entity.c1', 'like', '%word'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', 'search word', SearchTerm::OPERAND_OR, 666]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                    ],
                ],
            ]),
        ];
    }
}

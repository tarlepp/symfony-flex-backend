<?php
declare(strict_types = 1);
/**
 * /tests/Unit/Rest/SearchTermTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Unit\Rest;

use App\Rest\SearchTerm;
use App\Tests\Utils\StringableArrayObject;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function call_user_func_array;

/**
 * @package App\Tests\Unit\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class SearchTermTest extends KernelTestCase
{
    #[DataProvider('dataProviderTestThatWithoutColumnOrSearchTermCriteriaIsNull')]
    #[TestDox('Test that `getCriteria` method returns null with `$column` + `$search` parameters')]
    public function testThatWithoutColumnOrSearchTermCriteriaIsNull(
        string|StringableArrayObject|null $column,
        string|StringableArrayObject|null $search,
    ): void {
        if ($column instanceof StringableArrayObject) {
            /** @var array<int, string> $column */
            $column = $column->getArrayCopy();
        }

        if ($search instanceof StringableArrayObject) {
            /** @var array<int, string> $search */
            $search = $search->getArrayCopy();
        }

        self::assertNull(SearchTerm::getCriteria($column, $search), 'Criteria was not NULL with given parameters');
    }

    #[DataProvider('dataProviderTestThatReturnedCriteriaIsExpected')]
    #[TestDox('Test that `getCriteria` method returns `$expected` with given `$inputArguments` arguments')]
    public function testThatReturnedCriteriaIsExpected(
        StringableArrayObject $inputArguments,
        StringableArrayObject|null $expected
    ): void {
        /**
         * @var array{
         *      column: array<int, string>|string|null,
         *      search: array<int, string>|string|null,
         *      operand: string|null,
         *      mode: int|null,
         *  } $input
         */
        $input = $inputArguments->getArrayCopy();

        self::assertSame(
            $expected?->getArrayCopy(),
            call_user_func_array(SearchTerm::getCriteria(...), $input),
        );
    }

    /**
     * Data provider for testThatWithoutColumnOrSearchTermCriteriaIsNull
     *
     * @return Generator<array{0: null|string|StringableArrayObject, 1: null|string|StringableArrayObject}>
     */
    public static function dataProviderTestThatWithoutColumnOrSearchTermCriteriaIsNull(): Generator
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
     *
     * @return Generator<array{0: StringableArrayObject, 1: StringableArrayObject|null}>
     */
    public static function dataProviderTestThatReturnedCriteriaIsExpected(): Generator
    {
        // To cover array_filter on search term
        yield [
            new StringableArrayObject(['c1', '0', null, null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%0%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject([null, null, null, null]),
            null,
        ];

        yield [
            new StringableArrayObject(['c1', null, null, null]),
            null,
        ];

        yield [
            new StringableArrayObject([null, 'word', null, null]),
            null,
        ];

        yield [
            new StringableArrayObject(['', '', null, null]),
            null,
        ];

        yield [
            new StringableArrayObject(['c1', '', null, null]),
            null,
        ];

        yield [
            new StringableArrayObject(['', 'word', null, null]),
            null,
        ];

        yield [
            new StringableArrayObject(['c1', 'word', null, null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', 'word     ', null, null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject([['c1', 'c2'], ['search', 'word'], null, null]),
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
            new StringableArrayObject([['c1', 'c2'], ['   search', '   word    '], null, null]),
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
            new StringableArrayObject([['c1', 'c2'], ['search word'], null, null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search word%'],
                        ['entity.c2', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject([['c1', 'c2'], ['    search     word    '], null, null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search word%'],
                        ['entity.c2', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject([['c1', 'c2'], 'search word', null, null]),
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
            new StringableArrayObject([['c1', 'c2'], '   search   word   ', null, null]),
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
            new StringableArrayObject([['c1', 'c2'], '"search word"', null, null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search word%'],
                        ['entity.c2', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject([['c1', 'c2'], '  "  search  word  "  ', null, null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search word%'],
                        ['entity.c2', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['someTable.c1', 'search word', null, null]),
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
            new StringableArrayObject(['someTable.c1', '    search     word    ', null, null]),
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
            new StringableArrayObject(['someTable.c1', '"search word"', null, null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['someTable.c1', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['someTable.c1', '"    search    word   "', null, null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['someTable.c1', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['someTable.c1', ['search', 'word'], null, null]),
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
            new StringableArrayObject(['someTable.c1', ['    search', 'word   ', '   foo    bar   '], null, null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['someTable.c1', 'like', '%search%'],
                        ['someTable.c1', 'like', '%word%'],
                        ['someTable.c1', 'like', '%foo bar%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['someTable.c1', ['search word'], null, null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['someTable.c1', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['someTable.c1', ['    search    word   '], null, null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['someTable.c1', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject([['c1', 'someTable.c1'], 'search word', null, null]),
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
            new StringableArrayObject([['c1', 'someTable.c1'], '    search    word   ', null, null]),
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
            new StringableArrayObject([['c1', 'someTable.c1'], 'search word "word search"', null, null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['someTable.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                        ['someTable.c1', 'like', '%word%'],
                        ['entity.c1', 'like', '%word search%'],
                        ['someTable.c1', 'like', '%word search%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject([[
                'c1', 'someTable.c1'], '   search    word     "    word      search   "', null, null,
            ]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['someTable.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                        ['someTable.c1', 'like', '%word%'],
                        ['entity.c1', 'like', '%word search%'],
                        ['someTable.c1', 'like', '%word search%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject([['c1', 'someTable.c1'], ['search', 'word'], null, null]),
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
            new StringableArrayObject([
                ['c1', 'someTable.c1'], ['   search', 'word   ', '   foo   bar   '], null, null,
            ]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['someTable.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                        ['someTable.c1', 'like', '%word%'],
                        ['entity.c1', 'like', '%foo bar%'],
                        ['someTable.c1', 'like', '%foo bar%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject([['c1', 'someTable.c1'], ['search word'], null, null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search word%'],
                        ['someTable.c1', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject([['c1', 'someTable.c1'], ['   search    word   '], null, null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search word%'],
                        ['someTable.c1', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', 'search word', SearchTerm::OPERAND_AND, null]),
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
            new StringableArrayObject(['c1', '   search    word   ', SearchTerm::OPERAND_AND, null]),
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
            new StringableArrayObject(['c1', '"search word"', SearchTerm::OPERAND_AND, null]),
            new StringableArrayObject([
                'and' => [
                    'and' => [
                        ['entity.c1', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', '"    search     word   "', SearchTerm::OPERAND_AND, null]),
            new StringableArrayObject([
                'and' => [
                    'and' => [
                        ['entity.c1', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', 'search word', SearchTerm::OPERAND_OR, null]),
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
            new StringableArrayObject(['c1', '   search    word   ', SearchTerm::OPERAND_OR, null]),
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
            new StringableArrayObject(['c1', '"search word"', SearchTerm::OPERAND_OR, null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', '"    search     word   "', SearchTerm::OPERAND_OR, null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', 'search word', 'notSupportedOperand', null]),
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
            new StringableArrayObject(['c1', '    search     word    ', 'notSupportedOperand', null]),
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
            new StringableArrayObject(['c1', '"search word"', 'notSupportedOperand', null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', '"    search    word   "', 'notSupportedOperand', null]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search word%'],
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
            new StringableArrayObject(['c1', '    search    word    ', SearchTerm::OPERAND_OR, SearchTerm::MODE_FULL]),
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
            new StringableArrayObject(['c1', '"search word"', SearchTerm::OPERAND_OR, SearchTerm::MODE_FULL]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', '"    search    word   "', SearchTerm::OPERAND_OR, SearchTerm::MODE_FULL]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search word%'],
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
            new StringableArrayObject(['c1', '  search  word  ', SearchTerm::OPERAND_OR, SearchTerm::MODE_STARTS_WITH]),
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
            new StringableArrayObject(['c1', '"search word"', SearchTerm::OPERAND_OR, SearchTerm::MODE_STARTS_WITH]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', 'search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', ' " search word "', SearchTerm::OPERAND_OR, SearchTerm::MODE_STARTS_WITH]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', 'search word%'],
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
            new StringableArrayObject(['c1', '  search   word  ', SearchTerm::OPERAND_OR, SearchTerm::MODE_ENDS_WITH]),
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
            new StringableArrayObject(['c1', '"search word"', SearchTerm::OPERAND_OR, SearchTerm::MODE_ENDS_WITH]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search word'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', '"  search   word "', SearchTerm::OPERAND_OR, SearchTerm::MODE_ENDS_WITH]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search word'],
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

        yield [
            new StringableArrayObject(['c1', '  search word  foobar   ', SearchTerm::OPERAND_OR, 666]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                        ['entity.c1', 'like', '%foobar%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', '"search word"', SearchTerm::OPERAND_OR, 666]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];

        yield [
            new StringableArrayObject(['c1', '  "  search   word  "  ', SearchTerm::OPERAND_OR, 666]),
            new StringableArrayObject([
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search word%'],
                    ],
                ],
            ]),
        ];
    }
}

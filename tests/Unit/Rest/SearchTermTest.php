<?php
declare(strict_types=1);
/**
 * /tests/Unit/Rest/SearchTermTest.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Unit\Rest;

use App\Rest\SearchTerm;
use function call_user_func_array;
use Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class SearchTermTest
 *
 * @package App\Tests\Unit\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class SearchTermTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatWithoutColumnOrSearchTermCriteriaIsNull
     *
     * @param mixed $column
     * @param mixed $search
     */
    public function testThatWithoutColumnOrSearchTermCriteriaIsNull($column, $search): void
    {
        static::assertNull(SearchTerm::getCriteria($column, $search), 'Criteria was not NULL with given parameters');
    }

    /**
     * @dataProvider dataProviderTestThatReturnedCriteriaIsExpected
     *
     * @param array $inputArguments
     * @param array $expected
     */
    public function testThatReturnedCriteriaIsExpected(array $inputArguments, array $expected): void
    {
        static::assertSame($expected, call_user_func_array([SearchTerm::class, 'getCriteria'], $inputArguments));
    }

    /**
     * Data provider for testThatWithoutColumnOrSearchTermCriteriaIsNull
     *
     * @return Generator
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
        yield [[], []];
        yield [[null], [null]];
        yield [['foo'], [null]];
        yield [[null], ['foo']];
        yield [[''], ['']];
        yield [[' '], ['']];
        yield [[''], [' ']];
        yield [[' '], [' ']];
        yield [['foo'], ['']];
        yield [['foo'], [' ']];
        yield [[''], ['foo']];
        yield [[' '], ['foo']];
    }

    /**
     * Data provider for testThatReturnedCriteriaIsExpected
     *
     * @return Generator
     */
    public function dataProviderTestThatReturnedCriteriaIsExpected(): Generator
    {

        yield [
            ['c1', 'word'],
            [
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%word%'],
                    ],
                ],
            ],
        ];

        yield [
            [['c1', 'c2'], ['search', 'word']],
            [
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['entity.c2', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                        ['entity.c2', 'like', '%word%'],
                    ],
                ],
            ],
        ];

        yield [
            [['c1', 'c2'], 'search word'],
            [
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['entity.c2', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                        ['entity.c2', 'like', '%word%'],
                    ],
                ],
            ],
        ];

        yield [
            ['someTable.c1', 'search word'],
            [
                'and' => [
                    'or' => [
                        ['someTable.c1', 'like', '%search%'],
                        ['someTable.c1', 'like', '%word%'],
                    ],
                ],
            ],
        ];

        yield [
            ['someTable.c1', ['search', 'word']],
            [
                'and' => [
                    'or' => [
                        ['someTable.c1', 'like', '%search%'],
                        ['someTable.c1', 'like', '%word%'],
                    ],
                ],
            ],
        ];

        yield [
            [['c1', 'someTable.c1'], 'search word'],
            [
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['someTable.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                        ['someTable.c1', 'like', '%word%'],
                    ],
                ],
            ],
        ];

        yield [
            [['c1', 'someTable.c1'], ['search', 'word']],
            [
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['someTable.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                        ['someTable.c1', 'like', '%word%'],
                    ],
                ],
            ],
        ];

        yield [
            ['c1', 'search word', SearchTerm::OPERAND_AND],
            [
                'and' => [
                    'and' => [
                        ['entity.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                    ],
                ],
            ],
        ];

        yield [
            ['c1', 'search word', SearchTerm::OPERAND_OR],
            [
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                    ],
                ],
            ],
        ];

        yield [
            ['c1', 'search word', 'notSupportedOperand'],
            [
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                    ],
                ],
            ],
        ];

        yield [
            ['c1', 'search word', SearchTerm::OPERAND_OR, SearchTerm::MODE_FULL],
            [
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                    ],
                ],
            ],
        ];

        yield [
            ['c1', 'search word', SearchTerm::OPERAND_OR, SearchTerm::MODE_STARTS_WITH],
            [
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', 'search%'],
                        ['entity.c1', 'like', 'word%'],
                    ],
                ],
            ],
        ];

        yield [
            ['c1', 'search word', SearchTerm::OPERAND_OR, SearchTerm::MODE_ENDS_WITH],
            [
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search'],
                        ['entity.c1', 'like', '%word'],
                    ],
                ],
            ],
        ];

        yield [
            ['c1', 'search word', SearchTerm::OPERAND_OR, 666],
            [
                'and' => [
                    'or' => [
                        ['entity.c1', 'like', '%search%'],
                        ['entity.c1', 'like', '%word%'],
                    ],
                ],
            ],
        ];
    }
}

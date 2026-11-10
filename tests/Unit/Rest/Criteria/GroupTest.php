<?php
declare(strict_types = 1);

namespace App\Tests\Unit\Rest\Criteria;

use App\Rest\Criteria\Comparison;
use App\Rest\Criteria\Group;
use App\Tests\Utils\StringableArrayObject;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use function count;

final class GroupTest extends TestCase
{
    public function testThatEmptyGroupsPreserveTheirConjunction(): void
    {
        $and = Group::fromArray([]);
        $or = Group::fromArray([], false);
        $filters = Group::fromFilters([]);

        self::assertTrue($and->conjunction);
        self::assertSame([], $and->children);
        self::assertFalse($or->conjunction);
        self::assertSame([], $or->children);
        self::assertTrue($filters->conjunction);
        self::assertSame([], $filters->children);
    }

    public function testThatNestedGroupsPreserveOrderAndConjunctions(): void
    {
        $group = Group::fromArray([
            ['entity.first', 'eq', 1],
            'or' => [
                ['entity.second', 'eq', 2],
                [
                    'and' => [
                        ['entity.third', 'gt', 3],
                        ['entity.fourth', 'lt', 4],
                    ],
                ],
            ],
            ['entity.last', 'eq', 5],
        ]);

        self::assertTrue($group->conjunction);
        self::assertCount(3, $group->children);
        self::assertEquals(Comparison::fromArray(['entity.first', 'eq', 1]), $group->children[0]);

        $or = $group->children[1];

        self::assertInstanceOf(Group::class, $or);
        self::assertFalse($or->conjunction);
        self::assertCount(2, $or->children);
        self::assertEquals(Comparison::fromArray(['entity.second', 'eq', 2]), $or->children[0]);

        $wrapper = $or->children[1];

        self::assertInstanceOf(Group::class, $wrapper);
        self::assertTrue($wrapper->conjunction);
        self::assertCount(1, $wrapper->children);

        $and = $wrapper->children[0];

        self::assertInstanceOf(Group::class, $and);
        self::assertTrue($and->conjunction);
        self::assertEquals([
            Comparison::fromArray(['entity.third', 'gt', 3]),
            Comparison::fromArray(['entity.fourth', 'lt', 4]),
        ], $and->children);
        self::assertEquals(Comparison::fromArray(['entity.last', 'eq', 5]), $group->children[2]);
    }

    public function testThatWrapperGroupsKeepAndPrecedenceAndEmptyChildren(): void
    {
        $group = Group::fromArray([[
            'or' => [],
            'and' => [],
        ]]);

        self::assertCount(1, $group->children);

        $wrapper = $group->children[0];

        self::assertInstanceOf(Group::class, $wrapper);
        self::assertTrue($wrapper->conjunction);
        self::assertCount(2, $wrapper->children);
        self::assertEquals(Group::fromArray([], false), $wrapper->children[0]);
        self::assertEquals(Group::fromArray([]), $wrapper->children[1]);
    }

    public function testThatFiltersNormalizeNamesAndScalarsWhilePreservingLists(): void
    {
        $group = Group::fromFilters([
            'id' => 12,
            'related.active' => false,
            'ratio' => 1.5,
            'name' => new StringableArrayObject([1]),
            'ids' => [
                'first' => 1,
                4 => 'text',
            ],
            7 => true,
        ]);

        self::assertTrue($group->conjunction);

        $expected = [
            ['entity.id', 'eq', '12'],
            ['related.active', 'eq', ''],
            ['entity.ratio', 'eq', '1.5'],
            ['entity.name', 'eq', '[1]'],
            [
                'entity.ids',
                'in',
                [
                    'first' => 1,
                    4 => 'text',
                ],
            ],
            ['entity.7', 'eq', '1'],
        ];

        self::assertCount(count($expected), $group->children);

        foreach ($expected as $index => [$field, $operator, $value]) {
            $comparison = $group->children[$index];

            self::assertInstanceOf(Comparison::class, $comparison);
            self::assertSame($field, $comparison->field);
            self::assertSame($operator, $comparison->operator);
            self::assertSame($value, $comparison->value);
        }
    }

    public function testThatFiltersPreserveInterspersedAdvancedGroups(): void
    {
        $criteria = [
            'first' => 'one',
            'or' => [['entity.second', 'neq', 2]],
            'last' => [],
            'and' => [],
        ];

        self::assertEquals(
            Group::fromArray([
                ['entity.first', 'eq', 'one'],
                'or' => [['entity.second', 'neq', 2]],
                ['entity.last', 'in', []],
                'and' => [],
            ]),
            Group::fromFilters($criteria),
        );
    }

    /**
     * @param array<array-key, mixed> $criteria Invalid raw filters or groups to validate.
     */
    #[DataProvider('invalidCriteria')]
    public function testThatInvalidCriteriaAreRejected(array $criteria, bool $filters, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs($message);

        if ($filters) {
            Group::fromFilters($criteria);
        } else {
            Group::fromArray($criteria);
        }
    }

    /**
     * @return Generator<string, array{array<array-key, mixed>, bool, string}>
     */
    public static function invalidCriteria(): Generator
    {
        $groupMessage = 'Criteria groups must contain comparison arrays or nested groups.';
        $filterGroupMessage = 'Criteria AND/OR groups must be arrays.';
        $filterMessage = 'Criteria filters must be scalars, stringable objects, or arrays.';

        yield 'scalar child' => [[1], false, $groupMessage];

        yield 'scalar AND' => [[
            'and' => 'invalid',
        ], false, $groupMessage];

        yield 'nested scalar OR' => [[
            'and' => [[
                'or' => null,
            ]],
        ], false, $groupMessage];

        yield 'filter scalar AND' => [[
            'and' => false,
        ], true, $filterGroupMessage];

        yield 'filter null OR' => [[
            'or' => null,
        ], true, $filterGroupMessage];

        yield 'null filter' => [[
            'id' => null,
        ], true, $filterMessage];

        yield 'object filter' => [[
            'id' => new stdClass(),
        ], true, $filterMessage];

        yield 'invalid nested tuple' => [
            [
                'or' => [['entity.id', 'eq']],
            ],
            false,
            'A criteria comparison must contain exactly a field, operator, and value.',
        ];

        yield 'invalid filter list' => [
            [
                'id' => [null],
            ],
            true,
            'Criteria list and range operands must be scalars or stringable objects.',
        ];
    }
}

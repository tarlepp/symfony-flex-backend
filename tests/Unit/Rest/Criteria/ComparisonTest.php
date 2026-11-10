<?php
declare(strict_types = 1);

namespace App\Tests\Unit\Rest\Criteria;

use App\Rest\Criteria\Comparison;
use App\Tests\Utils\StringableArrayObject;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;

/**
 * @phpstan-import-type Value from Comparison
 */
final class ComparisonTest extends TestCase
{
    /**
     * @phpstan-param Value $value Valid operand whose value and keys must be preserved.
     */
    #[DataProvider('validOperands')]
    public function testThatOperandsArePreserved(
        string $operator,
        string|int|float|bool|Stringable|array|null $value,
    ): void {
        $comparison = Comparison::fromArray(['entity.id', $operator, $value]);

        self::assertSame('entity.id', $comparison->field);
        self::assertSame($operator, $comparison->operator);
        self::assertSame($value, $comparison->value);
    }

    public function testThatStringableOperandsArePreservedWithoutConversion(): void
    {
        $value = new StringableArrayObject([1]);

        self::assertSame($value, Comparison::fromArray(['entity.id', 'eq', $value])->value);
        self::assertSame([$value], Comparison::fromArray(['entity.id', 'in', [$value]])->value);
    }

    public function testThatTupleValuesUseInsertionOrderRatherThanKeys(): void
    {
        $comparison = Comparison::fromArray([
            'first' => 'entity.name',
            7 => 'NOTLIKE',
            'last' => 'text',
        ]);

        self::assertSame('entity.name', $comparison->field);
        self::assertSame('notLike', $comparison->operator);
        self::assertSame('text', $comparison->value);
    }

    public function testThatOtherDoctrineExpressionMethodsRemainAvailable(): void
    {
        $comparison = Comparison::fromArray(['entity.name', 'CONCAT', 'suffix']);

        self::assertSame('concat', $comparison->operator);
    }

    /**
     * @param array<array-key, mixed> $input Invalid raw tuple to validate.
     */
    #[DataProvider('invalidComparisons')]
    public function testThatMalformedComparisonsAreRejected(array $input, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs($message);

        Comparison::fromArray($input);
    }

    /**
     * @return Generator<string, array{string, Value}>
     */
    public static function validOperands(): Generator
    {
        yield 'string' => ['eq', 'text'];

        yield 'integer' => ['eq', 42];

        yield 'float' => ['gt', 1.5];

        yield 'boolean' => ['eq', false];

        yield 'null check' => ['isNull', null];

        yield 'not null check' => ['isNotNull', null];

        yield 'empty list' => ['in', []];

        yield 'keyed list' => [
            'notIn', [
                'first' => 1,
                4 => 'text',
                'last' => true,
            ]];

        yield 'range' => ['between', [1, 2]];

        yield 'reversed bound insertion order' => [
            'between', [
                1 => 2,
                0 => 1,
            ]];
    }

    /**
     * @return Generator<string, array{array<array-key, mixed>, string}>
     */
    public static function invalidComparisons(): Generator
    {
        $tupleMessage = 'A criteria comparison must contain exactly a field, operator, and value.';
        $identifierMessage = 'Criteria fields and operators must be strings.';
        $listMessage = 'Criteria list and range operands must be scalars or stringable objects.';
        $rangeMessage = 'The BETWEEN operator requires exactly two indexed bounds.';
        $inMessage = 'The IN and NOT IN operators require an array operand.';

        yield 'empty tuple' => [[], $tupleMessage];

        yield 'missing operand' => [['entity.id', 'eq'], $tupleMessage];

        yield 'extra operand' => [['entity.id', 'eq', 1, 2], $tupleMessage];

        yield 'non-string field' => [[1, 'eq', 1], $identifierMessage];

        yield 'non-string operator' => [['entity.id', false, 1], $identifierMessage];

        yield 'unknown operator' => [['entity.id', 'missing', 1], 'Unknown criteria expression operator.'];

        yield 'private method' => [['entity.id', 'quoteLiteral', 1], 'Unknown criteria expression operator.'];

        yield 'object operand' => [
            ['entity.id', 'eq', new stdClass()],
            'Criteria operands must be scalars, stringable objects, arrays, or null.',
        ];

        yield 'null list item' => [['entity.id', 'in', [null]], $listMessage];

        yield 'nested list item' => [['entity.id', 'in', [[1]]], $listMessage];

        yield 'object list item' => [['entity.id', 'in', [new stdClass()]], $listMessage];

        yield 'scalar IN' => [['entity.id', 'IN', 1], $inMessage];

        yield 'null NOT IN' => [['entity.id', 'NOTIN', null], $inMessage];

        yield 'scalar BETWEEN' => [['entity.id', 'BETWEEN', 1], $rangeMessage];

        yield 'empty BETWEEN' => [['entity.id', 'between', []], $rangeMessage];

        yield 'short BETWEEN' => [['entity.id', 'between', [1]], $rangeMessage];

        yield 'long BETWEEN' => [['entity.id', 'between', [1, 2, 3]], $rangeMessage];

        yield 'non-zero BETWEEN indexes' => [[
            'entity.id', 'between', [
                1 => 1,
                2 => 2,
            ]], $rangeMessage];

        yield 'named BETWEEN bounds' => [[
            'entity.id', 'between', [
                'min' => 1,
                'max' => 2,
            ]], $rangeMessage];
    }
}

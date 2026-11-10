<?php
declare(strict_types = 1);

namespace App\Rest\Criteria;

use Doctrine\ORM\Query\Expr;
use InvalidArgumentException;
use Stringable;
use function array_values;
use function count;
use function get_class_methods;
use function in_array;
use function is_array;
use function is_scalar;
use function is_string;
use function strcasecmp;
use function strtolower;

/**
 * @phpstan-type Operand scalar|Stringable
 * @phpstan-type Value Operand|null|array<array-key, Operand>
 */
final readonly class Comparison
{
    /**
     * @param Value $value Scalar operand, null for null checks, or values for list/range comparisons.
     */
    private function __construct(
        public string $field,
        public string $operator,
        public string|int|float|bool|Stringable|array|null $value,
    ) {
    }

    /**
     * @param array<array-key, mixed> $comparison Raw field, operator, and operand supplied by a caller.
     */
    public static function fromArray(array $comparison): self
    {
        if (count($comparison) !== 3) {
            throw new InvalidArgumentException(
                'A criteria comparison must contain exactly a field, operator, and value.',
            );
        }

        [$field, $operator, $value] = array_values($comparison);

        if (!is_string($field) || !is_string($operator)) {
            throw new InvalidArgumentException('Criteria fields and operators must be strings.');
        }

        if (is_array($value)) {
            $elements = [];

            foreach ($value as $key => $element) {
                if (!is_scalar($element) && !$element instanceof Stringable) {
                    throw new InvalidArgumentException(
                        'Criteria list and range operands must be scalars or stringable objects.',
                    );
                }

                $elements[$key] = $element;
            }

            $value = $elements;
        } elseif ($value !== null && !is_scalar($value) && !$value instanceof Stringable) {
            throw new InvalidArgumentException(
                'Criteria operands must be scalars, stringable objects, arrays, or null.',
            );
        }

        $normalizedOperator = strtolower($operator);

        if ($normalizedOperator === 'between'
            && (!is_array($value) || count($value) !== 2 || !isset($value[0], $value[1]))
        ) {
            throw new InvalidArgumentException('The BETWEEN operator requires exactly two indexed bounds.');
        }

        if (!is_array($value) && in_array($normalizedOperator, ['in', 'notin'], true)) {
            throw new InvalidArgumentException('The IN and NOT IN operators require an array operand.');
        }

        // Resolve the callable name from Doctrine rather than forwarding caller-controlled text.
        foreach (get_class_methods(Expr::class) as $method) {
            if (strcasecmp($method, $operator) === 0) {
                return new self($field, $method, $value);
            }
        }

        throw new InvalidArgumentException('Unknown criteria expression operator.');
    }
}

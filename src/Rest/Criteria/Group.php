<?php
declare(strict_types = 1);

namespace App\Rest\Criteria;

use InvalidArgumentException;
use Stringable;
use function array_key_exists;
use function is_array;
use function is_scalar;
use function str_contains;

final readonly class Group
{
    /**
     * @param list<Comparison|self> $children Comparisons and nested groups in their original traversal order.
     */
    private function __construct(
        public bool $conjunction,
        public array $children,
    ) {
    }

    /**
     * @param array<array-key, mixed> $criteria Raw comparisons and nested AND/OR groups.
     */
    public static function fromArray(array $criteria, bool $conjunction = true): self
    {
        $children = [];

        foreach ($criteria as $key => $comparison) {
            if (!is_array($comparison)) {
                throw new InvalidArgumentException('Criteria groups must contain comparison arrays or nested groups.');
            }

            if ($key === 'and' || array_key_exists('and', $comparison)) {
                $children[] = self::fromArray($comparison);
            } elseif ($key === 'or' || array_key_exists('or', $comparison)) {
                $children[] = self::fromArray($comparison, false);
            } else {
                $children[] = Comparison::fromArray($comparison);
            }
        }

        return new self($conjunction, $children);
    }

    /**
     * @param array<array-key, mixed> $criteria Field filters interspersed with advanced AND/OR groups.
     */
    public static function fromFilters(array $criteria): self
    {
        $comparisons = [];

        foreach ($criteria as $column => $value) {
            if ($column === 'and' || $column === 'or') {
                if (!is_array($value)) {
                    throw new InvalidArgumentException('Criteria AND/OR groups must be arrays.');
                }

                $comparisons[$column] = $value;
            } else {
                if (!is_array($value) && !is_scalar($value) && !$value instanceof Stringable) {
                    throw new InvalidArgumentException(
                        'Criteria filters must be scalars, stringable objects, or arrays.',
                    );
                }

                $field = (string)$column;
                $field = str_contains($field, '.') ? $field : 'entity.' . $field;
                $comparisons[] = [$field, is_array($value) ? 'in' : 'eq', is_array($value) ? $value : (string)$value];
            }
        }

        return self::fromArray($comparisons);
    }
}

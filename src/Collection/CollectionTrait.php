<?php
declare(strict_types = 1);
/**
 * /src/Collection/CollectionTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Collection;

use CallbackFilterIterator;
use Closure;
use InvalidArgumentException;
use Iterator;
use Traversable;
use function iterator_count;

/**
 * Trait CollectionTrait
 *
 * @package App\Collection
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method __construct(Traversable $items)
 */
trait CollectionTrait
{
    /**
     * @var Traversable
     */
    private $items;

    /**
     * @param string $className
     *
     * @return Closure
     */
    abstract public function filter(string $className): Closure;

    /**
     * @param string $className
     *
     * @throws InvalidArgumentException
     */
    abstract public function error(string $className): void;

    /**
     * Getter method for RestResource class.
     *
     * @param string $className
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     */
    public function get(string $className)
    {
        /** @var Iterator $items */
        $items = $this->getFilteredIterator($className)->getAll();
        $items->rewind();

        $current = $items->current();

        if ($current === null) {
            $this->error($className);
        }

        return $current;
    }

    /**
     * @return Traversable
     */
    public function getAll(): Traversable
    {
        return $this->items;
    }

    /**
     * Method to check if specified resource exists or not in this Collection.
     *
     * @param string|null $className
     *
     * @return bool
     */
    public function has(?string $className = null): bool
    {
        return $this->getFilteredIterator((string)$className)->count() === 1;
    }

    /**
     * Count elements of an object.
     *
     * @return int
     */
    public function count(): int
    {
        return iterator_count($this->items);
    }

    /**
     * @param string $className
     *
     * @return $this
     */
    private function getFilteredIterator(string $className): self
    {
        /** @psalm-suppress UndefinedInterfaceMethod */
        return new self(new CallbackFilterIterator($this->items->getIterator(), $this->filter($className)));
    }
}

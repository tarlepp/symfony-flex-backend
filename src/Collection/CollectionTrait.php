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
use IteratorAggregate;
use IteratorIterator;
use function iterator_count;

/**
 * Trait CollectionTrait
 *
 * @package App\Collection
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait CollectionTrait
{
    /**
     * @var IteratorAggregate
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
        $current = $this->getFilteredItem($className);

        if ($current === null) {
            $this->error($className);
        }

        return $current;
    }

    /**
     * @return IteratorAggregate
     */
    public function getAll(): IteratorAggregate
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
        return $this->getFilteredItem((string)$className) !== null;
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
     * @return null|mixed
     */
    private function getFilteredItem(string $className)
    {
        $filteredIterator = new CallbackFilterIterator(
            new IteratorIterator($this->items->getIterator()),
            $this->filter($className)
        );
        $filteredIterator->rewind();

        return $filteredIterator->current();
    }
}

<?php
declare(strict_types = 1);
/**
 * /src/Collection/CollectionTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Collection;

use CallbackFilterIterator;
use Closure;
use InvalidArgumentException;
use IteratorAggregate;
use IteratorIterator;
use Throwable;
use function iterator_count;

/**
 * @package App\Collection
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
trait CollectionTrait
{
    /**
     * Method to filter current collection.
     *
     * @psalm-var class-string $className
     */
    abstract public function filter(string $className): Closure;

    /**
     * Method to process error message for current collection.
     *
     * @psalm-var class-string $className
     *
     * @throws InvalidArgumentException
     */
    abstract public function getErrorMessage(string $className): string;

    /**
     * Getter method for given class for current collection.
     *
     * @throws InvalidArgumentException
     */
    public function get(string $className): mixed
    {
        return $this->getFilteredItem($className)
            ?? throw new InvalidArgumentException($this->getErrorMessage($className));
    }

    /**
     * Method to get all items from current collection.
     *
     * @return IteratorAggregate<mixed>
     */
    public function getAll(): IteratorAggregate
    {
        return $this->items;
    }

    /**
     * Method to check if specified class exists or not in current collection.
     */
    public function has(?string $className = null): bool
    {
        return $this->getFilteredItem($className ?? '') !== null;
    }

    /**
     * Count elements of an object.
     */
    public function count(): int
    {
        return iterator_count($this->items);
    }

    private function getFilteredItem(string $className): mixed
    {
        try {
            $iterator = $this->items->getIterator();
        } catch (Throwable $throwable) {
            $this->logger->error($throwable->getMessage());

            return null;
        }

        $filteredIterator = new CallbackFilterIterator(new IteratorIterator($iterator), $this->filter($className));
        $filteredIterator->rewind();

        return $filteredIterator->current();
    }
}

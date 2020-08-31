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
use Psr\Log\LoggerInterface;
use Throwable;
use function iterator_count;

/**
 * Trait CollectionTrait
 *
 * @package App\Collection
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait CollectionTrait
{
    private IteratorAggregate $items;
    private LoggerInterface $logger;

    /**
     * Method to filter current collection.
     */
    abstract public function filter(string $className): Closure;

    /**
     * Method to process error message for current collection.
     *
     * @throws InvalidArgumentException
     */
    abstract public function error(string $className): void;

    /**
     * Getter method for given class for current collection.
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
     * Method to get all items from current collection.
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

    /**
     * @return mixed|null
     */
    private function getFilteredItem(string $className)
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

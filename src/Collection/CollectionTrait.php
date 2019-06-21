<?php
declare(strict_types=1);
/**
 * /src/Collection/CollectionTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Collection;

use Closure;
use InvalidArgumentException;
use Traversable;
use function array_filter;
use function count;
use function iterator_to_array;

/**
 * Trait CollectionTrait
 *
 * @package App\Collection
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
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
        $filteredResources = $this->getFilteredItems($className);

        if (count($filteredResources) !== 1) {
            $this->error($className);
        }

        return $filteredResources[0];
    }

    /**
     * @return Traversable
     * @psalm-return Traversable<array-key, mixed>
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
        return $className === null
            ? false
            : count(array_filter(iterator_to_array($this->items), $this->filter($className))) === 1;
    }

    /**
     * @param string $className
     *
     * @return array
     */
    private function getFilteredItems(string $className): array
    {
        return array_values(
            array_filter(
                iterator_to_array($this->items),
                $this->filter($className)
            )
        );
    }
}

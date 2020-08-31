<?php
declare(strict_types = 1);
/**
 * /src/Resource/ResourceCollection.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Resource;

use App\Collection\CollectionTrait;
use App\Rest\Interfaces\RestResourceInterface;
use CallbackFilterIterator;
use Closure;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use IteratorIterator;
use Psr\Log\LoggerInterface;
use Throwable;
use function sprintf;

/**
 * Class ResourceCollection
 *
 * @package App\Resource
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ResourceCollection implements Countable
{
    use CollectionTrait;

    /**
     * Collection constructor.
     *
     * @param IteratorAggregate<int, RestResourceInterface> $resources
     */
    public function __construct(IteratorAggregate $resources, LoggerInterface $logger)
    {
        $this->items = $resources;
        $this->logger = $logger;
    }

    /**
     * Getter method for REST resource by entity class name.
     */
    public function getEntityResource(string $className): RestResourceInterface
    {
        $current = $this->getFilteredItemByEntity($className);

        if ($current === null) {
            $message = sprintf(
                'Resource class does not exist for entity \'%s\'',
                $className
            );

            throw new InvalidArgumentException($message);
        }

        return $current;
    }

    /**
     * Method to check if specified entity class REST resource exists or not
     * in current collection.
     */
    public function hasEntityResource(?string $className = null): bool
    {
        return $this->getFilteredItemByEntity($className ?? '') !== null;
    }

    public function filter(string $className): Closure
    {
        return static fn (RestResourceInterface $restResource): bool => $restResource instanceof $className;
    }

    public function error(string $className): void
    {
        $message = sprintf(
            'Resource \'%s\' does not exist',
            $className
        );

        throw new InvalidArgumentException($message);
    }

    /**
     * Getter method to get filtered item by given entity class.
     */
    private function getFilteredItemByEntity(string $entityName): ?RestResourceInterface
    {
        try {
            $iterator = $this->items->getIterator();
        } catch (Throwable $throwable) {
            $this->logger->error($throwable->getMessage());

            return null;
        }

        $callback = static fn (RestResourceInterface $resource): bool => $resource->getEntityName() === $entityName;

        $filteredIterator = new CallbackFilterIterator(new IteratorIterator($iterator), $callback);
        $filteredIterator->rewind();

        return $filteredIterator->current();
    }
}

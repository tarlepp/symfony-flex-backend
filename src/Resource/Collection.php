<?php
declare(strict_types = 1);
/**
 * /src/Resource/Collection.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Resource;

use App\Collection\CollectionTrait;
use App\Rest\RestResourceInterface;
use Closure;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use function sprintf;

/**
 * Class Collection
 *
 * @package App\Resource
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @property IteratorAggregate|IteratorAggregate<int, RestResourceInterface> $items
 *
 * @method RestResourceInterface                         get(string $className)
 * @method IteratorAggregate<int, RestResourceInterface> getAll(): IteratorAggregate
 */
class Collection implements Countable
{
    // Traits
    use CollectionTrait;

    /**
     * Collection constructor.
     *
     * @param IteratorAggregate|IteratorAggregate<int, RestResourceInterface> $resources
     */
    public function __construct(IteratorAggregate $resources)
    {
        $this->items = $resources;
    }

    /**
     * @param string|null $className
     *
     * @return Closure
     */
    public function filter(?string $className): Closure
    {
        return static function (RestResourceInterface $restResource) use ($className): bool {
            return $className !== null && $restResource instanceof $className;
        };
    }

    /**
     * @param string $className
     *
     * @throws InvalidArgumentException
     */
    public function error(string $className): void
    {
        $message = sprintf(
            'Resource \'%s\' does not exists',
            $className
        );

        throw new InvalidArgumentException($message);
    }
}

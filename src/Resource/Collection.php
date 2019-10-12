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
use Traversable;
use function sprintf;

/**
 * Class Collection
 *
 * @package App\Resource
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @property Traversable|Traversable<int, RestResourceInterface> $items
 *
 * @method RestResourceInterface                   get(string $className)
 * @method Traversable<int, RestResourceInterface> getAll(): Traversable
 */
class Collection implements Countable
{
    // Traits
    use CollectionTrait;

    /**
     * Collection constructor.
     *
     * @param Traversable|Traversable<RestResourceInterface> $resources
     */
    public function __construct(Traversable $resources)
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

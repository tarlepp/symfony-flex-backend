<?php
declare(strict_types = 1);
/**
 * /src/Rest/ControllerCollection.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest;

use App\Collection\CollectionTrait;
use Closure;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;

/**
 * Class ControllerCollection
 *
 * @package App\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @property IteratorAggregate|IteratorAggregate<int, ControllerInterface> $items
 *
 * @method ControllerInterface                         get(string $className)
 * @method IteratorAggregate<int, ControllerInterface> getAll(): IteratorAggregate
 */
class ControllerCollection implements Countable
{
    // Traits
    use CollectionTrait;

    /**
     * Collection constructor.
     *
     * @param IteratorAggregate|IteratorAggregate<int, ControllerInterface> $controllers
     */
    public function __construct(IteratorAggregate $controllers)
    {
        $this->items = $controllers;
    }

    /**
     * @param string $className
     *
     * @throws InvalidArgumentException
     */
    public function error(string $className): void
    {
        $message = sprintf(
            'REST controller \'%s\' does not exists',
            $className
        );

        throw new InvalidArgumentException($message);
    }

    /**
     * @param string $className
     *
     * @return Closure
     */
    public function filter(string $className): Closure
    {
        return static function (ControllerInterface $restController) use ($className): bool {
            return $restController instanceof $className;
        };
    }
}

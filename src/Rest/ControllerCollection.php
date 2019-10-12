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
use Traversable;

/**
 * Class ControllerCollection
 *
 * @package App\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @property Traversable|Traversable<int, ControllerInterface> $items
 *
 * @method ControllerInterface                   get(string $className)
 * @method Traversable<int, ControllerInterface> getAll(): Traversable
 */
class ControllerCollection implements Countable
{
    // Traits
    use CollectionTrait;

    /**
     * Collection constructor.
     *
     * @param Traversable|Traversable<ControllerInterface> $controllers
     */
    public function __construct(Traversable $controllers)
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
     * @param string|null $className
     *
     * @return Closure
     */
    public function filter(?string $className): Closure
    {
        return static function (ControllerInterface $restController) use ($className): bool {
            return $className !== null && $restController instanceof $className;
        };
    }
}

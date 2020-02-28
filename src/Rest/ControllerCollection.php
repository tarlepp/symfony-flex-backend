<?php
declare(strict_types = 1);
/**
 * /src/Rest/ControllerCollection.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest;

use App\Collection\CollectionTrait;
use App\Rest\Interfaces\ControllerInterface;
use Closure;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Psr\Log\LoggerInterface;
use function sprintf;

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
     * @param LoggerInterface                                               $logger
     */
    public function __construct(IteratorAggregate $controllers, LoggerInterface $logger)
    {
        $this->items = $controllers;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function filter(string $className): Closure
    {
        return static fn (ControllerInterface $restController): bool => $restController instanceof $className;
    }
}

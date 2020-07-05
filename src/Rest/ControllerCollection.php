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
 */
class ControllerCollection implements Countable
{
    use CollectionTrait;

    /**
     * Collection constructor.
     *
     * @param IteratorAggregate<int, ControllerInterface> $controllers
     */
    public function __construct(IteratorAggregate $controllers, LoggerInterface $logger)
    {
        $this->items = $controllers;
        $this->logger = $logger;
    }

    public function error(string $className): void
    {
        $message = sprintf(
            'REST controller \'%s\' does not exist',
            $className
        );

        throw new InvalidArgumentException($message);
    }

    public function filter(string $className): Closure
    {
        return static fn (ControllerInterface $restController): bool => $restController instanceof $className;
    }
}

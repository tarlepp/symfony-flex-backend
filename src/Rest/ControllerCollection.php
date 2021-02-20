<?php
declare(strict_types = 1);
/**
 * /src/Rest/ControllerCollection.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
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
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method ControllerInterface get()
 * @method IteratorAggregate<int, ControllerInterface> getAll()
 */
class ControllerCollection implements Countable
{
    use CollectionTrait;

    /**
     * Collection constructor.
     *
     * @param IteratorAggregate<int, ControllerInterface> $items
     */
    public function __construct(
        private IteratorAggregate $items,
        private LoggerInterface $logger,
    ) {
    }

    public function error(string $className): void
    {
        throw new InvalidArgumentException(sprintf('REST controller \'%s\' does not exist', $className));
    }

    public function filter(string $className): Closure
    {
        return static fn (ControllerInterface $restController): bool => $restController instanceof $className;
    }
}

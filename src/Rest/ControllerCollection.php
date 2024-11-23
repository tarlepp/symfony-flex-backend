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
use IteratorAggregate;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use function sprintf;

/**
 * @package App\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method ControllerInterface get(string $className)
 * @method IteratorAggregate<int, ControllerInterface> getAll()
 *
 * @template T of ControllerInterface
 */
class ControllerCollection implements Countable
{
    use CollectionTrait;

    /**
     * @phpstan-param IteratorAggregate<int, ControllerInterface> $items
     */
    public function __construct(
        #[AutowireIterator('app.rest.controller')]
        protected readonly IteratorAggregate $items,
        protected readonly LoggerInterface $logger,
    ) {
    }

    #[Override]
    public function getErrorMessage(string $className): string
    {
        return sprintf('REST controller \'%s\' does not exist', $className);
    }

    #[Override]
    public function filter(string $className): Closure
    {
        return static fn (ControllerInterface $restController): bool => $restController instanceof $className;
    }
}

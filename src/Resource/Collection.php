<?php
declare(strict_types = 1);
/**
 * /src/Resource/Collection.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Resource;

use App\Rest\RestResourceInterface;
use Closure;
use InvalidArgumentException;
use Traversable;
use function array_filter;
use function array_values;
use function count;
use function iterator_to_array;
use function sprintf;

/**
 * Class Collection
 *
 * @package App\Resource
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class Collection
{
    /**
     * @var Traversable|Traversable<RestResourceInterface>
     */
    private $resources;

    /**
     * Collection constructor.
     *
     * @param Traversable|Traversable<RestResourceInterface> $resources
     */
    public function __construct(Traversable $resources)
    {
        $this->resources = $resources;
    }

    /**
     * Getter method to get _all_ resources.
     *
     * @return Traversable|Traversable<RestResourceInterface>
     */
    public function getAll(): Traversable
    {
        return $this->resources;
    }

    /**
     * Getter method for RestResource class.
     *
     * @param string $resourceName
     *
     * @return RestResourceInterface
     *
     * @throws InvalidArgumentException
     */
    public function get(string $resourceName): RestResourceInterface
    {
        $filteredResources = array_values(
            array_filter(
                iterator_to_array($this->resources),
                $this->resourceFilter($resourceName)
            )
        );

        if (count($filteredResources) !== 1) {
            $message = sprintf(
                'Resource \'%s\' does not exists',
                $resourceName
            );

            throw new InvalidArgumentException($message);
        }

        return $filteredResources[0];
    }

    /**
     * Method to check if specified resource exists or not in this Collection.
     *
     * @param string|null $resourceName
     *
     * @return bool
     */
    public function has(?string $resourceName = null): bool
    {
        return count(array_filter(iterator_to_array($this->resources), $this->resourceFilter($resourceName))) === 1;
    }

    /**
     * @param string|null $resourceName
     *
     * @return Closure
     */
    private function resourceFilter(?string $resourceName): Closure
    {
        return static function (RestResourceInterface $restResource) use ($resourceName): bool {
            return $resourceName !== null && $restResource instanceof $resourceName;
        };
    }
}

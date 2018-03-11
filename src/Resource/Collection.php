<?php
declare(strict_types = 1);
/**
 * /src/Resource/Collection.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Resource;

use App\Rest\RestResource;
use InvalidArgumentException;
use function array_key_exists;
use function get_class;
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
     * @var RestResource[]
     */
    private $resources = [];

    /**
     * Getter method for RestResource class.
     *
     * @param string $resourceName
     *
     * @return RestResource
     *
     * @throws InvalidArgumentException
     */
    public function get(string $resourceName): RestResource
    {
        if ($this->has($resourceName) === false) {
            $message = sprintf(
                'Resource \'%s\' does not exists',
                $resourceName
            );

            throw new InvalidArgumentException($message);
        }

        return $this->resources[$resourceName];
    }

    /**
     * Setter method for RestResource class.
     *
     * @param RestResource $resource
     *
     * @return Collection
     */
    public function set(RestResource $resource): self
    {
        $this->resources[get_class($resource)] = $resource;

        return $this;
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
        $output = false;

        if ($resourceName !== null && array_key_exists($resourceName, $this->resources)) {
            $output = true;
        }

        return $output;
    }
}

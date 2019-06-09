<?php
declare(strict_types = 1);
/**
 * /src/Rest/ControllerCollection.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest;

use Closure;
use InvalidArgumentException;
use Traversable;
use function array_filter;
use function array_values;
use function count;
use function iterator_to_array;

/**
 * Class ControllerCollection
 *
 * @package App\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ControllerCollection
{
    /**
     * @var Traversable|Traversable<ControllerInterface>
     */
    private $controllers;

    /**
     * Collection constructor.
     *
     * @param Traversable|Traversable<ControllerInterface> $controllers
     */
    public function __construct(Traversable $controllers)
    {
        $this->controllers = $controllers;
    }

    /**
     * Getter method to get _all_ REST controllers.
     *
     * @return Traversable|Traversable<ControllerInterface>
     */
    public function getAll(): Traversable
    {
        return $this->controllers;
    }

    /**
     * Getter method for RestResource class.
     *
     * @param string $controllerName
     *
     * @return ControllerInterface
     *
     * @throws InvalidArgumentException
     */
    public function get(string $controllerName): ControllerInterface
    {
        $filteredControllers = array_values(
            array_filter(
                iterator_to_array($this->controllers),
                $this->controllerFilter($controllerName)
            )
        );

        if (count($filteredControllers) !== 1) {
            $message = sprintf(
                'REST controller \'%s\' does not exists',
                $controllerName
            );

            throw new InvalidArgumentException($message);
        }

        return $filteredControllers[0];
    }

    /**
     * Method to check if specified resource exists or not in this Collection.
     *
     * @param string|null $controllerName
     *
     * @return bool
     */
    public function has(?string $controllerName = null): bool
    {
        return count(
            array_filter(
                iterator_to_array($this->controllers),
                $this->controllerFilter($controllerName)
            )
        ) === 1;
    }

    /**
     * @param string|null $controllerName
     *
     * @return Closure
     */
    private function controllerFilter(?string $controllerName): Closure
    {
        return static function (ControllerInterface $restController) use ($controllerName): bool {
            return $controllerName !== null && $restController instanceof $controllerName;
        };
    }
}

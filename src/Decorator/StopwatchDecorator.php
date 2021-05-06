<?php
declare(strict_types = 1);
/**
 * /src/Decorator/StopwatchDecorator.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Decorator;

use App\Entity\Interfaces\EntityInterface;
use ProxyManager\Factory\AccessInterceptorValueHolderFactory;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Stopwatch\Stopwatch;
use Throwable;
use function array_filter;
use function is_object;
use function str_starts_with;

/**
 * Class StopwatchDecorator
 *
 * @package App\Decorator
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class StopwatchDecorator
{
    public function __construct(
        private AccessInterceptorValueHolderFactory $factory,
        private Stopwatch $stopwatch,
    ) {
    }

    public function decorate(object $service): object
    {
        $class = new ReflectionClass($service);
        $className = $class->getName();

        // Do not process core or extensions or already wrapped services
        if ($class->getFileName() === false || str_starts_with($class->getName(), 'ProxyManagerGeneratedProxy')) {
            return $service;
        }

        $prefix = [];
        $suffix = [];

        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
        $methods = array_filter($methods, static fn ($method): bool => !$method->isStatic() && !$method->isFinal());

        foreach ($methods as $method) {
            $methodName = $method->getName();
            $eventName = "{$class->getShortName()}->{$methodName}";

            $prefix[$methodName] = function () use ($eventName, $className): void {
                $this->stopwatch->start($eventName, $className);
            };

            $suffix[$methodName] = function (
                mixed $p,
                mixed $i,
                mixed $m,
                mixed $params,
                mixed &$returnValue
            ) use ($eventName): void {
                $this->stopwatch->stop($eventName);

                /**
                 * Decorate returned values as well
                 *
                 * Note that this might cause some weird errors on some edge
                 * cases - we should fix those when those happens...
                 */
                if (is_object($returnValue) && !$returnValue instanceof EntityInterface) {
                    $returnValue = $this->decorate($returnValue);
                }
            };
        }

        try {
            $output = $this->factory->createProxy($service, $prefix, $suffix);
        } catch (Throwable) {
            $output = $service;
        }

        return $output;
    }
}

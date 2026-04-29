<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Decorator/ServiceWithNeverReturnType.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Decorator;

use RuntimeException;

/**
 * Helper class for testing StopwatchDecorator with `never`-returning methods.
 *
 * A `never` return type means the method will always throw an exception or call exit/die.
 * The proxy generator must treat `never` like `void` (no `return` statement in the proxy body),
 * otherwise PHP would raise a fatal error when compiling the generated proxy class.
 *
 * @psalm-suppress ClassMustBeFinal
 */
class ServiceWithNeverReturnType
{
    public function alwaysThrows(): never
    {
        throw new RuntimeException('This method never returns normally');
    }
}

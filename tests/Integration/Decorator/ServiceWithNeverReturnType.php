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
 * Helper class for testing StopwatchDecorator's error handling.
 *
 * When the decorator tries to proxy this class it generates invalid PHP code
 * (a `return` statement inside a `never`-typed method), which causes eval()
 * to throw a compile-time fatal error. The decorator must fall back to
 * returning the original, un-proxied service instance.
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

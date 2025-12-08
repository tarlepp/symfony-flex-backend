<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Decorator/ServiceWithInternalDefaults.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Decorator;

/**
 * Helper class for testing internal class default values
 *
 * @psalm-suppress ClassMustBeFinal
 */
class ServiceWithInternalDefaults
{
    /**
     * Method with parameter that has complex default values
     * This is for testing the catch block in getDefaultValueString when the decorator
     * tries to get the default value via reflection for certain edge cases
     *
     * @param array<string, mixed> $options
     */
    public function methodWithInternalDefault(array $options = []): string
    {
        return 'test';
    }
}

<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Decorator/ServiceWithInternalDefaults.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Decorator;

use SplFileInfo;

/**
 * Helper class for testing internal class default values
 * Extends an internal class to potentially trigger reflection edge cases
 */
class ServiceWithInternalDefaults extends SplFileInfo
{
    public function __construct()
    {
        parent::__construct(__FILE__);
    }

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

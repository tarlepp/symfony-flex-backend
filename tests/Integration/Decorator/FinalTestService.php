<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Decorator/FinalTestService.php
 */

namespace App\Tests\Integration\Decorator;

/**
 * Helper final class for testing
 */
final class FinalTestService
{
    public function testMethod(): string
    {
        return 'final-test';
    }
}

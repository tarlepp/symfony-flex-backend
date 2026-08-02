<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Helpers/src/StopwatchAwareService.php
 */

namespace App\Tests\Integration\Helpers\src;

use App\Helpers\StopwatchAwareTrait;

final class StopwatchAwareService
{
    use StopwatchAwareTrait;

    public function __construct()
    {
        $this->stopwatch = null;
    }
}

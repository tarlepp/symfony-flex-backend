<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Helpers/src/LoggerAwareService.php
 */

namespace App\Tests\Integration\Helpers\src;

use App\Helpers\LoggerAwareTrait;

final class LoggerAwareService
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = null;
    }
}

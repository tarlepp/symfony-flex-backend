<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Helpers/src/LoggerAwareService.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Helpers\src;

use App\Helpers\LoggerAwareTrait;

/**
 * @package App\Tests\Integration\Helpers\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class LoggerAwareService
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = null;
    }
}

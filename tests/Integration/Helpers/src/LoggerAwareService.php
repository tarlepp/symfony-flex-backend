<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Helpers/src/LoggerAwareService.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Helpers\src;

use App\Helpers\LoggerAwareTrait;

/**
 * Class LoggerAwareService
 *
 * @package App\Tests\Integration\Helpers\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LoggerAwareService
{
    use LoggerAwareTrait;
}

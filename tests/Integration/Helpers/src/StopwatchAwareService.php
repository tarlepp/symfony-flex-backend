<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Helpers/src/StopwatchAwareService.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Helpers\src;

use App\Helpers\StopwatchAwareTrait;

/**
 * Class StopwatchAwareService
 *
 * @package App\Tests\Integration\Helpers\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class StopwatchAwareService
{
    use StopwatchAwareTrait;
}

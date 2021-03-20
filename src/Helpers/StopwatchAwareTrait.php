<?php
declare(strict_types = 1);
/**
 * /src/Helpers/StopwatchAwareTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Helpers;

use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Class StopwatchAwareTrait
 *
 * NOTE: Do not use this in your services, just inject `Stopwatch` to service
 *       where you need it. This trait is just for quick debug purposes and
 *       nothing else.
 *
 * @package App\Helpers
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
trait StopwatchAwareTrait
{
    protected ?Stopwatch $stopwatch;

    #[Required]
    public function setStopwatch(Stopwatch $stopwatch): self
    {
        $this->stopwatch = $stopwatch;

        return $this;
    }
}

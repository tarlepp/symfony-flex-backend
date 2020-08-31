<?php
declare(strict_types = 1);
/**
 * /src/Helpers/StopwatchAwareTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Helpers;

use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Class StopwatchAwareTrait
 *
 * NOTE: Do not use this in your services, just inject `Stopwatch` to service
 *       where you need it. This trait is just for quick debug purposes and
 *       nothing else.
 *
 * @package App\Helpers
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait StopwatchAwareTrait
{
    protected Stopwatch $stopwatch;

    /**
     * @see https://symfony.com/doc/current/service_container/autowiring.html#autowiring-other-methods-e-g-setters
     *
     * @required
     */
    public function setStopwatch(Stopwatch $stopwatch): self
    {
        $this->stopwatch = $stopwatch;

        return $this;
    }
}

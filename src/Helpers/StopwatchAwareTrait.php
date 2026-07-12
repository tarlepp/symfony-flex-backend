<?php
declare(strict_types = 1);

/**
 * /src/Helpers/StopwatchAwareTrait.php
 */

namespace App\Helpers;

use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * NOTE: Do not use this in your services, just inject `Stopwatch` to service
 *       where you need it. This trait is just for quick debug purposes and
 *       nothing else.
 */
trait StopwatchAwareTrait
{
    protected ?Stopwatch $stopwatch = null;

    #[Required]
    public function setStopwatch(Stopwatch $stopwatch): self
    {
        $this->stopwatch = $stopwatch;

        return $this;
    }
}

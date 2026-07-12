<?php
declare(strict_types = 1);

/**
 * /src/Helpers/LoggerAwareTrait.php
 */

namespace App\Helpers;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * NOTE: Do not use this in your services, just inject `LoggerInterface` to
 *       service where you need it. This trait is just for quick debug purposes
 *       and nothing else.
 */
trait LoggerAwareTrait
{
    protected ?LoggerInterface $logger = null;

    #[Required]
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }
}

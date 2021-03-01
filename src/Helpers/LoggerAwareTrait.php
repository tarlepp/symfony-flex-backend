<?php
declare(strict_types = 1);
/**
 * /src/Helpers/LoggerAwareTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Helpers;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Trait LoggerAwareTrait
 *
 * NOTE: Do not use this in your services, just inject `LoggerInterface` to
 *       service where you need it. This trait is just for quick debug purposes
 *       and nothing else.
 *
 * @package App\Helpers
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
trait LoggerAwareTrait
{
    protected LoggerInterface $logger;

    #[Required]
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }
}

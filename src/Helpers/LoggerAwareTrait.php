<?php
declare(strict_types = 1);
/**
 * /src/Helpers/LoggerAwareTrait.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Helpers;

use Psr\Log\LoggerInterface;

/**
 * Trait LoggerAwareTrait
 *
 * @package App\Helpers
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait LoggerAwareTrait
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @see https://symfony.com/doc/current/service_container/autowiring.html#autowiring-other-methods-e-g-setters
     *
     * @required
     *
     * @param LoggerInterface $logger
     *
     * @return self
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }
}

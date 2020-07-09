<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/RestMethodProcessCriteria.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Methods;

use Symfony\Component\HttpFoundation\Request;

/**
 * Trait RestMethodProcessCriteria
 *
 * @package App\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait RestMethodProcessCriteria
{
    public function processCriteria(array &$criteria, Request $request, string $method): void
    {
    }
}

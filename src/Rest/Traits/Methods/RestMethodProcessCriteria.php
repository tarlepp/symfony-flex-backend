<?php
declare(strict_types = 1);

/**
 * /src/Rest/Traits/RestMethodProcessCriteria.php
 */

namespace App\Rest\Traits\Methods;

use Symfony\Component\HttpFoundation\Request;

trait RestMethodProcessCriteria
{
    /**
     * @param array<string, string|array<mixed>> $criteria
     */
    public function processCriteria(array &$criteria, Request $request, string $method): void
    {
    }
}

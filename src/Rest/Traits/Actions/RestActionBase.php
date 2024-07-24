<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/RestActionBase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Rest\Traits\Actions;

use App\Rest\Interfaces\RestResourceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @package App\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
trait RestActionBase
{
    /**
     * @param array<int, string> $allowedHttpMethods
     */
    public function getResourceForMethod(Request $request, array $allowedHttpMethods): RestResourceInterface
    {
        // Make sure that we have everything we need to make this work
        $this->validateRestMethod($request, $allowedHttpMethods);

        // Get current resource service
        return $this->getResource();
    }
}

<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/MethodHelper.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Actions;

use App\Rest\Interfaces\RestResourceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Trait MethodHelper
 *
 * @package App\Rest\Traits\Methods
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait RestActionBase
{
    public function getResourceForMethod(Request $request, array $allowedHttpMethods): RestResourceInterface
    {
        // Make sure that we have everything we need to make this work
        $this->validateRestMethod($request, $allowedHttpMethods);

        // Get current resource service
        return $this->getResource();
    }
}

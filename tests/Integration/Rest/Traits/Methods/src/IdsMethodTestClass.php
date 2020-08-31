<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/IdsMethodTestClass.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods\src;

use App\Rest\Controller;
use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Rest\Traits\Methods\IdsMethod;

/**
 * Class IdsMethodTestClass - just a dummy class so that we can actually test that trait.
 *
 * @package App\Tests\Integration\Rest\Traits\Methods\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class IdsMethodTestClass extends Controller
{
    use IdsMethod;

    /**
     * FindMethodTestClass constructor.
     */
    public function __construct(RestResourceInterface $resource, ResponseHandlerInterface $responseHandler)
    {
        $this->resource = $resource;

        $this->responseHandler = $responseHandler;
    }
}

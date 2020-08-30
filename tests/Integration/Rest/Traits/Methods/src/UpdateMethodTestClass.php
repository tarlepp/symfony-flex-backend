<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/UpdateMethodTestClass.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods\src;

use App\Rest\Controller;
use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Rest\Traits\Methods\UpdateMethod;

/**
 * Class UpdateMethodTestClass - just a dummy class so that we can actually test that trait.
 *
 * @package App\Tests\Integration\Rest\Traits\Methods\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class UpdateMethodTestClass extends Controller
{
    use UpdateMethod;

    /**
     * UpdateMethodTestClass constructor.
     */
    public function __construct(RestResourceInterface $resource, ResponseHandlerInterface $responseHandler)
    {
        $this->resource = $resource;

        $this->responseHandler = $responseHandler;
    }
}

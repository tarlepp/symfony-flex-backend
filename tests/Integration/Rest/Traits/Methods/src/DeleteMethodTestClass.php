<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/DeleteMethodTestClass.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods\src;

use App\Rest\Controller;
use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Rest\Traits\Methods\DeleteMethod;

/**
 * Class DeleteMethodTestClass - just a dummy class so that we can actually test that trait.
 *
 * @package App\Tests\Integration\Rest\Traits\Methods\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class DeleteMethodTestClass extends Controller
{
    use DeleteMethod;

    /**
     * DeleteMethodTestClass constructor.
     */
    public function __construct(RestResourceInterface $resource, ResponseHandlerInterface $responseHandler)
    {
        $this->resource = $resource;

        $this->responseHandler = $responseHandler;
    }
}

<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Rest/Traits/Methods/UpdateMethodTestClass.php
 */

namespace App\Tests\Integration\Rest\Traits\Methods\src;

use App\Rest\Controller;
use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Rest\Traits\Methods\UpdateMethod;

/**
 * Class UpdateMethodTestClass - just a dummy class so that we can actually test that trait.
 */
final class UpdateMethodTestClass extends Controller
{
    use UpdateMethod;

    public function __construct(
        RestResourceInterface $resource,
        ResponseHandlerInterface $responseHandler,
    ) {
        parent::__construct($resource);

        $this->responseHandler = $responseHandler;
    }
}

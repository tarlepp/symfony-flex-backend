<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Rest/Traits/Methods/src/PatchMethodTestClass.php
 */

namespace App\Tests\Integration\Rest\Traits\Methods\src;

use App\Rest\Controller;
use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Rest\Traits\Methods\PatchMethod;

/**
 * Class PatchMethodTestClass - just a dummy class so that we can actually test that trait.
 */
final class PatchMethodTestClass extends Controller
{
    use PatchMethod;

    public function __construct(
        RestResourceInterface $resource,
        ResponseHandlerInterface $responseHandler,
    ) {
        parent::__construct($resource);

        $this->responseHandler = $responseHandler;
    }
}

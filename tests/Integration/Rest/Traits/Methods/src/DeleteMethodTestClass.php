<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Rest/Traits/Methods/DeleteMethodTestClass.php
 */

namespace App\Tests\Integration\Rest\Traits\Methods\src;

use App\Rest\Controller;
use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Rest\Traits\Methods\DeleteMethod;

/**
 * Class DeleteMethodTestClass - just a dummy class so that we can actually test that trait.
 */
final class DeleteMethodTestClass extends Controller
{
    use DeleteMethod;

    public function __construct(
        RestResourceInterface $resource,
        ResponseHandlerInterface $responseHandler,
    ) {
        parent::__construct($resource);

        $this->responseHandler = $responseHandler;
    }
}

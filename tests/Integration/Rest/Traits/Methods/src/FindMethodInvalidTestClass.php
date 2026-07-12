<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Rest/Traits/Methods/FindMethodInvalidTestClass.php
 */

namespace App\Tests\Integration\Rest\Traits\Methods\src;

use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Rest\Traits\Actions\RestActionBase;
use App\Rest\Traits\Methods\FindMethod;
use App\Rest\Traits\RestMethodHelper;
use BadMethodCallException;

/**
 * Class FindMethodInvalidTestClass - just a dummy class so that we can actually test that trait.
 */
final class FindMethodInvalidTestClass
{
    use FindMethod;
    use RestActionBase;
    use RestMethodHelper;

    public function getResource(): RestResourceInterface
    {
        throw new BadMethodCallException('This method should not be called.');
    }

    public function getResponseHandler(): ResponseHandlerInterface
    {
        throw new BadMethodCallException('This method should not be called.');
    }
}

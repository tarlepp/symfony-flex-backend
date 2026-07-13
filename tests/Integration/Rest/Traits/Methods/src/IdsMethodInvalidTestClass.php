<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Rest/Traits/Methods/IdsMethodInvalidTestClass.php
 */

namespace App\Tests\Integration\Rest\Traits\Methods\src;

use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Rest\Traits\Actions\RestActionBase;
use App\Rest\Traits\Methods\IdsMethod;
use App\Rest\Traits\RestMethodHelper;
use BadMethodCallException;

/**
 * Class IdsMethodInvalidTestClass - just a dummy class so that we can actually test that trait.
 */
final class IdsMethodInvalidTestClass
{
    use IdsMethod;
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

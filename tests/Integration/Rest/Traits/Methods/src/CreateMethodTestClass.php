<?php
declare(strict_types=1);
/**
 * /tests/Integration/Rest/Traits/Methods/CreateMethodTestClass.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Rest\Traits\Methods\src;

use App\Rest\ControllerInterface;
use App\Rest\ResourceInterface;
use App\Rest\ResponseHandlerInterface;
use App\Rest\Traits\Methods\CreateMethod;
use App\Rest\Traits\MethodValidator;

/**
 * Class CreateMethodTestClass - just a dummy class so that we can actually test that trait.
 *
 * @package App\Tests\Integration\Rest\Traits\Methods\src
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class CreateMethodTestClass implements ControllerInterface
{
    use CreateMethod;
    use MethodValidator;

    /**
     * FindMethodTestClass constructor.
     *
     * @param ResourceInterface        $resource
     * @param ResponseHandlerInterface $responseHandler
     */
    public function __construct(ResourceInterface $resource, ResponseHandlerInterface $responseHandler)
    {
    }
}

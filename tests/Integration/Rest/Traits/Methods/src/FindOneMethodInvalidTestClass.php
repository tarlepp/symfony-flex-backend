<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/FindOneMethodInvalidTestClass.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods\src;

use App\Rest\Traits\Actions\RestActionBase;
use App\Rest\Traits\Methods\FindOneMethod;
use App\Rest\Traits\RestMethodHelper;

/**
 * Class FindOneMethodInvalidTestClass - just a dummy class so that we can actually test that trait.
 *
 * @package App\Tests\Integration\Rest\Traits\Methods\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class FindOneMethodInvalidTestClass
{
    use FindOneMethod;
    use RestActionBase;
    use RestMethodHelper;
}

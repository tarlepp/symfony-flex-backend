<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/IdsMethodInvalidTestClass.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods\src;

use App\Rest\Traits\Actions\RestActionBase;
use App\Rest\Traits\Methods\IdsMethod;
use App\Rest\Traits\RestMethodHelper;

/**
 * Class IdsMethodInvalidTestClass - just a dummy class so that we can actually test that trait.
 *
 * @package App\Tests\Integration\Rest\Traits\Methods\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class IdsMethodInvalidTestClass
{
    use IdsMethod;
    use RestActionBase;
    use RestMethodHelper;
}

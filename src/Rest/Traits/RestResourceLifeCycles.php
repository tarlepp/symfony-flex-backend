<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Resource.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits;

/**
 * Trait Resource
 *
 * @package App\Rest\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait RestResourceLifeCycles
{
    use RestResourceFind;
    use RestResourceFindOne;
    use RestResourceFindOneBy;
    use RestResourceCount;
    use RestResourceIds;
    use RestResourceCreate;
    use RestResourceUpdate;
    use RestResourcePatch;
    use RestResourceDelete;
    use RestResourceSave;
}

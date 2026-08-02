<?php
declare(strict_types = 1);

/**
 * /src/Rest/Traits/RestResourceLifeCycles.php
 */

namespace App\Rest\Traits;

/**
 * @codeCoverageIgnore Pure aggregation trait; all constituent traits are covered through RestResourceBaseMethods
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

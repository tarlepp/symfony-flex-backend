<?php
declare(strict_types = 1);
/**
 * /src/Entity/Traits/Uuid.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity\Traits;

use App\Rest\UuidHelper;
use Ramsey\Uuid\UuidInterface;

/**
 * Trait Uuid
 *
 * @package App\Entity\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait Uuid
{
    public function getUuid(): UuidInterface
    {
        return $this->id;
    }

    protected function createUuid(): UuidInterface
    {
        return UuidHelper::getFactory()->uuid1();
    }
}

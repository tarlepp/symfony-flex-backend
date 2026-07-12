<?php
declare(strict_types = 1);

/**
 * /src/Entity/Traits/Uuid.php
 */

namespace App\Entity\Traits;

use App\Rest\UuidHelper;
use Ramsey\Uuid\UuidInterface;

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

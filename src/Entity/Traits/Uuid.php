<?php
declare(strict_types = 1);
/**
 * /src/Entity/Traits/Uuid.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity\Traits;

use Ramsey\Uuid\Codec\OrderedTimeCodec;
use Ramsey\Uuid\UuidInterface;
use Throwable;

/**
 * Trait Uuid
 *
 * @package App\Entity\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait Uuid
{
    /**
     * @return UuidInterface
     *
     * @throws Throwable
     */
    protected function getUuid(): UuidInterface
    {
        $factory = clone \Ramsey\Uuid\Uuid::getFactory();
        $codec = new OrderedTimeCodec($factory->getUuidBuilder());

        $factory->setCodec($codec);

        return $factory->uuid1();
    }
}

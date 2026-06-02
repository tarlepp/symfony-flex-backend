<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/EntityRestResource.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest\Traits;

use App\Entity\Interfaces\EntityInterface;
use App\Rest\RestResource;

/**
 * @extends RestResource<EntityInterface>
 */
abstract class EntityRestResource extends RestResource
{
}

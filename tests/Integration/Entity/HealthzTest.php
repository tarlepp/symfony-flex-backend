<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/HealthzTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\Healthz;

/**
 * Class HealthzTest
 *
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method Healthz getEntity()
 */
class HealthzTest extends EntityTestCase
{
    /**
     * @var class-string
     */
    protected string $entityName = Healthz::class;
}

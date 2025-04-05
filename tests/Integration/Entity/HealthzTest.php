<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/HealthzTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\Healthz;
use App\Tests\Integration\TestCase\EntityTestCase;

/**
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method Healthz getEntity()
 */
final class HealthzTest extends EntityTestCase
{
    /**
     * @var class-string
     */
    protected static string $entityName = Healthz::class;
}

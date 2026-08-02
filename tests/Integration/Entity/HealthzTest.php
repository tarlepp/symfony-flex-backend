<?php
declare(strict_types = 1);

/**
 * /tests/Integration/Entity/HealthzTest.php
 */

namespace App\Tests\Integration\Entity;

use App\Entity\Healthz;
use App\Tests\Integration\TestCase\EntityTestCase;

/**
 * @method Healthz getEntity()
 */
final class HealthzTest extends EntityTestCase
{
    /**
     * @var class-string<\App\Entity\Interfaces\EntityInterface>
     */
    protected static string $entityName = Healthz::class;
}

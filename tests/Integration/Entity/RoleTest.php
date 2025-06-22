<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/RoleTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\Role;
use App\Tests\Integration\TestCase\EntityTestCase;
use Override;

/**
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method Role getEntity()
 */
final class RoleTest extends EntityTestCase
{
    /**
     * @var class-string
     */
    protected static string $entityName = Role::class;

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[Override]
    public function testThatGetIdReturnsCorrectUuid(): void
    {
        self::markTestSkipped();
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[Override]
    protected function createEntity(): Role
    {
        return new Role('Some role');
    }
}

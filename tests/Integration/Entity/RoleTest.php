<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/RoleTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\Role;

/**
 * Class RoleTest
 *
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method Role getEntity()
 */
class RoleTest extends EntityTestCase
{
    /**
     * @var class-string
     */
    protected string $entityName = Role::class;

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * Generic method to test that getId method returns a string and it is UUID V4 format
     */
    public function testThatGetIdReturnsCorrectUuid(): void
    {
        self::markTestSkipped();
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function createEntity(): Role
    {
        return new Role('Some role');
    }
}
